import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Trend, Rate, Counter } from 'k6/metrics';

// =============================================================================
// Teste de escalabilidade / carga da API de Ordens de Serviço
// Rodar com k6 (via container Docker — não precisa instalar nada):
//   docker run --rm -i --network=host grafana/k6 run - < load-tests/ordens-servico.js
// Variáveis de ambiente:
//   BASE_URL   (default http://localhost:8080/api)
//   EMAIL      (default admin@oficina.local)
//   PASSWORD   (default admin123)
// =============================================================================

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8080/api';
const EMAIL = __ENV.EMAIL || 'admin@oficina.local';
const PASSWORD = __ENV.PASSWORD || 'admin123';

// Métricas customizadas
const osCriadas = new Counter('os_criadas');
const loginDuration = new Trend('login_duration', true);
const criarOsDuration = new Trend('criar_os_duration', true);
const listarOsDuration = new Trend('listar_os_duration', true);
const errosNegocio = new Rate('erros_negocio');

// -----------------------------------------------------------------------------
// Perfis de carga: escolha via --stage/scenario. Aqui usamos "ramping" que sobe
// gradualmente para você observar o comportamento sob crescimento de carga.
// -----------------------------------------------------------------------------
export const options = {
  scenarios: {
    carga_crescente: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '30s', target: 10 },  // aquecimento
        { duration: '1m', target: 50 },   // sobe a carga
        { duration: '2m', target: 100 },  // pico sustentado (dispara HPA no k8s)
        { duration: '30s', target: 0 },   // desaceleração
      ],
      gracefulRampDown: '10s',
    },
  },
  thresholds: {
    http_req_duration: ['p(95)<800'],   // 95% das reqs abaixo de 800ms
    http_req_failed: ['rate<0.05'],     // menos de 5% de falhas HTTP
    erros_negocio: ['rate<0.05'],
  },
};

// -----------------------------------------------------------------------------
// Gera um CPF válido (com dígitos verificadores) — necessário porque o VO Cpf
// valida o checksum ao criar o cliente.
// -----------------------------------------------------------------------------
function gerarCpf() {
  const n = () => Math.floor(Math.random() * 9);
  const d = Array.from({ length: 9 }, n);
  const calcDig = (base) => {
    let soma = 0;
    for (let i = 0; i < base.length; i++) {
      soma += base[i] * (base.length + 1 - i);
    }
    const resto = (soma * 10) % 11;
    return resto === 10 ? 0 : resto;
  };
  const d1 = calcDig(d);
  const d2 = calcDig([...d, d1]);
  return [...d, d1, d2].join('');
}

function gerarPlaca() {
  const L = () => String.fromCharCode(65 + Math.floor(Math.random() * 26));
  const N = () => Math.floor(Math.random() * 10);
  // padrão Mercosul: LLLNLNN
  return `${L()}${L()}${L()}${N()}${L()}${N()}${N()}`;
}

// -----------------------------------------------------------------------------
// setup(): roda 1x antes do teste. Faz login e cria 1 cliente + 1 veículo
// que serão reutilizados por todos os VUs para criar as OS.
// -----------------------------------------------------------------------------
export function setup() {
  const loginRes = http.post(
    `${BASE_URL}/auth/login`,
    JSON.stringify({ email: EMAIL, password: PASSWORD }),
    { headers: { 'Content-Type': 'application/json', Accept: 'application/json' } }
  );
  check(loginRes, { 'login OK': (r) => r.status === 200 });
  const token = loginRes.json('access_token') || loginRes.json('token');
  if (!token) {
    throw new Error(`Falha no login: ${loginRes.status} ${loginRes.body}`);
  }
  const authHeaders = {
    headers: {
      Authorization: `Bearer ${token}`,
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
  };

  const clienteRes = http.post(
    `${BASE_URL}/clientes`,
    JSON.stringify({
      nome: 'Cliente Load Test',
      tipo: 'pf',
      documento: gerarCpf(),
      telefone: '11999999999',
      email: `loadtest_${Date.now()}@example.com`,
    }),
    authHeaders
  );
  check(clienteRes, { 'cliente criado': (r) => r.status === 201 });
  const clienteId = clienteRes.json('data.id') || clienteRes.json('id');

  const veiculoRes = http.post(
    `${BASE_URL}/veiculos`,
    JSON.stringify({
      cliente_id: clienteId,
      placa: gerarPlaca(),
      marca: 'Fiat',
      modelo: 'Uno',
      ano: 2020,
      cor: 'Prata',
    }),
    authHeaders
  );
  check(veiculoRes, { 'veiculo criado': (r) => r.status === 201 });
  const veiculoId = veiculoRes.json('data.id') || veiculoRes.json('id');

  return { token, clienteId, veiculoId };
}

// -----------------------------------------------------------------------------
// Fluxo executado por cada VU repetidamente durante o teste.
// -----------------------------------------------------------------------------
export default function (data) {
  const authHeaders = {
    headers: {
      Authorization: `Bearer ${data.token}`,
      'Content-Type': 'application/json',
      Accept: 'application/json',
    },
  };

  group('Criar OS', () => {
    const res = http.post(
      `${BASE_URL}/ordens-servico`,
      JSON.stringify({
        cliente_id: data.clienteId,
        veiculo_id: data.veiculoId,
        descricao_problema: `Barulho no motor - VU ${__VU} iter ${__ITER}`,
      }),
      authHeaders
    );
    criarOsDuration.add(res.timings.duration);
    const ok = check(res, { 'OS criada (201)': (r) => r.status === 201 });
    errosNegocio.add(!ok);
    if (ok) osCriadas.add(1);
  });

  group('Listar OS', () => {
    const res = http.get(`${BASE_URL}/ordens-servico`, authHeaders);
    listarOsDuration.add(res.timings.duration);
    check(res, { 'lista OK (200)': (r) => r.status === 200 });
  });

  sleep(1); // pausa simulando comportamento de usuário real
}

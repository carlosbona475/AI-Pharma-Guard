/**
 * AI Pharma Guard - API e utilitários
 * Todas as URLs usam caminho absoluto a partir da raiz do site: /backend/...
 *
 * Para instalar em subpasta (ex.: /meuapp/), defina antes de carregar este script:
 *   <script>window.API_BASE = '/meuapp/backend';</script>
 */
(function (global) {
    function resolveApiBase() {
        if (typeof global.API_BASE !== 'undefined' && global.API_BASE !== '') {
            return String(global.API_BASE).replace(/\/$/, '');
        }
        return '/backend';
    }

    var API_BASE = resolveApiBase();
    var API = API_BASE + '/api';

    function parseResponseAsJson(res, text) {
        var trimmed = (text || '').trim();
        if (!trimmed) {
            console.error('[API] Resposta vazia do servidor. HTTP', res.status, res.url);
            throw new Error('Resposta vazia do servidor (HTTP ' + res.status + ').');
        }
        if (trimmed.charAt(0) === '<') {
            console.error(
                '[API] Resposta não é JSON (HTML recebido). HTTP',
                res.status,
                res.url,
                trimmed.slice(0, 200)
            );
            throw new Error(
                'Erro HTTP ' +
                    res.status +
                    ': o servidor retornou HTML em vez de JSON. Verifique se a URL /backend está correta e se o PHP está acessível.'
            );
        }
        try {
            return JSON.parse(trimmed);
        } catch (e) {
            console.error('[API] JSON inválido. Amostra:', trimmed.slice(0, 200), e);
            throw new Error('Resposta inválida do servidor (não é JSON). HTTP ' + res.status + '.');
        }
    }

    /**
     * fetch + validação HTTP + parse JSON seguro (evita "Unexpected token '<'").
     */
    function fetchJson(url, fetchOpts) {
        var opts = Object.assign({ credentials: 'same-origin' }, fetchOpts || {});
        return fetch(url, opts)
            .then(function (res) {
                return res.text().then(function (text) {
                    var data;
                    try {
                        data = parseResponseAsJson(res, text);
                    } catch (parseErr) {
                        if (!res.ok) {
                            throw new Error(
                                'Erro HTTP ' + res.status + ': ' + (parseErr.message || 'Falha na resposta.')
                            );
                        }
                        throw parseErr;
                    }
                    if (!res.ok) {
                        var msg =
                            (data && (data.error || data.message)) ||
                            'Erro HTTP ' + res.status;
                        console.error('[API]', res.status, url, data || text);
                        throw new Error(msg);
                    }
                    return data;
                });
            })
            .catch(function (err) {
                console.error('[API] Falha na requisição:', url, err);
                if (err instanceof TypeError && err.message && err.message.indexOf('fetch') !== -1) {
                    throw new Error(
                        'Não foi possível conectar à API. Verifique o servidor e a URL base (' + API_BASE + ').'
                    );
                }
                throw err;
            });
    }

    function request(method, action, body) {
        var url = API_BASE + '/api.php?action=' + encodeURIComponent(action);
        var opts = { method: method, headers: { 'Content-Type': 'application/json' } };
        if (body && (method === 'POST' || method === 'PUT')) {
            opts.body = JSON.stringify(body);
        }
        return fetchJson(url, opts);
    }

    var api = {
        getEstatisticas: function () {
            return fetchJson(API + '/dashboard.php', {
                method: 'GET',
                headers: { 'Content-Type': 'application/json' },
            }).then(function (data) {
                return {
                    pacientes: data.total_pacientes,
                    medicamentos: data.total_medicamentos,
                    interacoes_cadastradas: data.total_interacoes,
                };
            });
        },

        listarPacientes: function (page, limit) {
            if (
                typeof page === 'number' &&
                page >= 1 &&
                typeof limit === 'number' &&
                limit >= 1
            ) {
                var url =
                    API_BASE +
                    '/api.php?action=listar_pacientes&page=' +
                    encodeURIComponent(page) +
                    '&limit=' +
                    encodeURIComponent(limit);
                return fetchJson(url, {
                    method: 'GET',
                    headers: { 'Content-Type': 'application/json' },
                });
            }
            return request('GET', 'listar_pacientes');
        },

        verificarAlergia: function (pacienteId, medicamentoNome) {
            return request('POST', 'verificar_alergia', {
                paciente_id: pacienteId,
                medicamento_nome: medicamentoNome,
            });
        },

        cadastrarPaciente: function (data) {
            return request('POST', 'cadastrar_paciente', data);
        },

        listarMedicamentos: function () {
            return fetchJson(API + '/medicamentos.php', {
                method: 'GET',
                headers: { 'Content-Type': 'application/json' },
            }).then(function (data) {
                return Array.isArray(data) ? data : [];
            });
        },

        cadastrarMedicamento: function (data) {
            return request('POST', 'cadastrar_medicamento', data);
        },

        verificarInteracoes: function (medicamentos) {
            return request('POST', 'verificar_interacoes', { medicamentos: medicamentos });
        },

        listarInteracoes: function () {
            return fetchJson(API + '/interacoes.php', {
                method: 'GET',
                headers: { 'Content-Type': 'application/json' },
            }).then(function (data) {
                return Array.isArray(data) ? data : [];
            });
        },

        cadastrarInteracao: function (data) {
            return request('POST', 'cadastrar_interacao', data);
        },

        analisarRiscoPaciente: function (pacienteId) {
            return request('POST', 'analisar_risco_paciente', { paciente_id: pacienteId });
        },

        verificarInteracoesPaciente: function (opts) {
            return request('POST', 'verificar_interacoes_paciente', opts);
        },
    };

    function escapeHtml(str) {
        if (str == null) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    global.api = api;
    global.escapeHtml = escapeHtml;
    /** Exposto para debug e testes */
    global.API_BASE_RESOLVED = API_BASE;
})(typeof window !== 'undefined' ? window : this);

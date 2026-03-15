/**
 * AI Pharma Guard - API e utilitários
 * Conexão com Supabase via backend PHP (backend/api/*.php).
 *
 * API_BASE: defina window.API_BASE antes de carregar para fixar.
 * Produção (Render): https://SEU-BACKEND.onrender.com/backend
 * Local: origin + '/backend' ou '../../backend'
 */
(function(global) {
    function resolveApiBase() {
        if (typeof global.API_BASE !== 'undefined' && global.API_BASE !== '') return global.API_BASE.replace(/\/$/, '');
        try {
            var origin = global.location && global.location.origin;
            var pathname = (global.location && global.location.pathname) || '';
            if (origin && pathname.indexOf('/frontend/') !== -1) return origin + '/backend';
            if (origin) return origin + '/backend';
        } catch (e) {}
        return '../../backend';
    }
    var API_BASE = resolveApiBase();
    var API = API_BASE + '/api';

    function parseResponseAsJson(res, text) {
        var trimmed = (text || '').trim();
        if (!trimmed) {
            var msg = '[API] Resposta vazia do servidor.';
            console.error(msg);
            throw new Error('Resposta vazia do servidor.');
        }
        if (trimmed.charAt(0) === '<') {
            var htmlMsg = '[API] Resposta não é JSON (servidor retornou HTML). Verifique URL da API e se o PHP está em execução. Amostra: ' + trimmed.slice(0, 120) + (trimmed.length > 120 ? '...' : '');
            console.error(htmlMsg);
            throw new Error(
                'O servidor retornou HTML em vez de JSON. Verifique a URL da API e se o PHP está em execução. Detalhes no console.'
            );
        }
        try {
            return JSON.parse(trimmed);
        } catch (e) {
            var invalidMsg = '[API] Resposta não é JSON válido. Amostra: ' + trimmed.slice(0, 150) + (trimmed.length > 150 ? '...' : '');
            console.error(invalidMsg, e);
            if (e instanceof SyntaxError) {
                throw new Error('Resposta inválida do servidor (não é JSON). Detalhes no console.');
            }
            throw new Error('Resposta inválida do servidor. Detalhes no console.');
        }
    }

    function request(method, action, body) {
        var url = API_BASE + '/api.php?action=' + encodeURIComponent(action);
        var opts = { method: method, headers: { 'Content-Type': 'application/json' } };
        if (body && (method === 'POST' || method === 'PUT')) opts.body = JSON.stringify(body);
        return fetch(url, opts)
            .then(function(res) {
                return res.text().then(function(text) {
                    var data = parseResponseAsJson(res, text);
                    if (!res.ok) {
                        var statusMsg = '[API] Erro HTTP ' + res.status + ': ' + (data.error || text);
                        console.error(statusMsg);
                        throw new Error(data.error || 'Erro ' + res.status);
                    }
                    return data;
                });
            })
            .catch(function(err) {
                console.error('[API] Falha na requisição:', err.message, err);
                if (err.message && (err.message.indexOf('JSON') !== -1 || err.message.indexOf('HTML') !== -1 || err.message.indexOf('Resposta') !== -1)) {
                    throw err;
                }
                if (err instanceof TypeError && err.message.indexOf('fetch') !== -1) {
                    var netMsg = 'Não foi possível conectar à API. Verifique o servidor e a URL (API_BASE).';
                    console.error('[API] ' + netMsg);
                    throw new Error(netMsg);
                }
                throw err;
            });
    }

    var api = {
        getEstatisticas: function() {
            return fetch(API + '/dashboard.php').then(function(res) {
                return res.text().then(function(text) {
                    var data = parseResponseAsJson(res, text);
                    if (!res.ok) throw new Error(data.error || data.message || 'Erro ao carregar dashboard');
                    return {
                        pacientes: data.total_pacientes,
                        medicamentos: data.total_medicamentos,
                        interacoes_cadastradas: data.total_interacoes
                    };
                });
            }).catch(function(err) {
                console.error('[API] getEstatisticas:', err);
                throw err;
            });
        },
        listarPacientes: function() {
            return request('GET', 'listar_pacientes');
        },
        cadastrarPaciente: function(data) {
            return request('POST', 'cadastrar_paciente', data);
        },
        listarMedicamentos: function() {
            return fetch(API + '/medicamentos.php').then(function(res) {
                return res.text().then(function(text) {
                    var data = parseResponseAsJson(res, text);
                    if (!res.ok) throw new Error(data.error || data.message || 'Erro ao carregar medicamentos');
                    return Array.isArray(data) ? data : [];
                });
            }).catch(function(err) {
                console.error('[API] listarMedicamentos:', err);
                throw err;
            });
        },
        cadastrarMedicamento: function(data) {
            return request('POST', 'cadastrar_medicamento', data);
        },
        verificarInteracoes: function(medicamentos) {
            return request('POST', 'verificar_interacoes', { medicamentos: medicamentos });
        },
        listarInteracoes: function() {
            return fetch(API + '/interacoes.php').then(function(res) {
                return res.text().then(function(text) {
                    var data = parseResponseAsJson(res, text);
                    if (!res.ok) throw new Error(data.error || data.message || 'Erro ao carregar interações');
                    return Array.isArray(data) ? data : [];
                });
            }).catch(function(err) {
                console.error('[API] listarInteracoes:', err);
                throw err;
            });
        },
        cadastrarInteracao: function(data) {
            return request('POST', 'cadastrar_interacao', data);
        },
        analisarRiscoPaciente: function(pacienteId) {
            return request('POST', 'analisar_risco_paciente', { paciente_id: pacienteId });
        }
    };

    function escapeHtml(str) {
        if (str == null) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    global.api = api;
    global.escapeHtml = escapeHtml;
})(typeof window !== 'undefined' ? window : this);

/**
 * AI Pharma Guard - API e utilitários
 * Base URL: ajuste se o backend estiver em outro caminho (ex: mesmo servidor = '/backend')
 */
(function(global) {
    var API_BASE = '../../backend';

    function request(method, action, body) {
        var url = API_BASE + '/api.php?action=' + encodeURIComponent(action);
        var opts = { method: method, headers: { 'Content-Type': 'application/json' } };
        if (body && (method === 'POST' || method === 'PUT')) {
            opts.body = JSON.stringify(body);
        }
        return fetch(url, opts).then(function(res) {
            if (!res.ok) {
                return res.json().then(function(data) {
                    throw new Error(data.error || 'Erro ' + res.status);
                }).catch(function() {
                    throw new Error('Erro ' + res.status);
                });
            }
            return res.json();
        });
    }

    var api = {
        getEstatisticas: function() {
            return request('GET', 'estatisticas');
        },
        listarPacientes: function() {
            return request('GET', 'listar_pacientes');
        },
        cadastrarPaciente: function(data) {
            return request('POST', 'cadastrar_paciente', data);
        },
        listarMedicamentos: function() {
            return request('GET', 'listar_medicamentos');
        },
        cadastrarMedicamento: function(data) {
            return request('POST', 'cadastrar_medicamento', data);
        },
        verificarInteracoes: function(medicamentos) {
            return request('POST', 'verificar_interacoes', { medicamentos: medicamentos });
        },
        listarInteracoes: function() {
            return request('GET', 'listar_interacoes');
        },
        cadastrarInteracao: function(data) {
            return request('POST', 'cadastrar_interacao', data);
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

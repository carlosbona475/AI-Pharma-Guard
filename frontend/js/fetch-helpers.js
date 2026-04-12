/**
 * Parse seguro de fetch (evita Unexpected token '<' quando o servidor devolve HTML).
 */
(function (g) {
    g.parseJsonFetchResponse = function (res) {
        return res.text().then(function (text) {
            var t = (text || '').trim();
            if (!t) {
                if (!res.ok) {
                    throw new Error('Erro HTTP ' + res.status + ': resposta vazia.');
                }
                return {};
            }
            if (t.charAt(0) === '<') {
                throw new Error(
                    'O servidor retornou HTML (HTTP ' +
                        res.status +
                        '). Verifique se a URL /backend está correta e o PHP está no ar.'
                );
            }
            var data;
            try {
                data = JSON.parse(t);
            } catch (e) {
                throw new Error('Resposta não é JSON válido (HTTP ' + res.status + ').');
            }
            if (!res.ok) {
                throw new Error(
                    (data && (data.message || data.error)) || 'Erro HTTP ' + res.status
                );
            }
            return data;
        });
    };
})(typeof window !== 'undefined' ? window : this);

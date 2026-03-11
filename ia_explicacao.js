// Função para explicar interações medicamentosas em linguagem simples
function explicarInteracoes(interacoes) {
    const explicacoes = interacoes.map(interacao => {
        return `Interação entre ${interacao.medicamentoA} e ${interacao.medicamentoB}: ` +
               `${interacao.tipo_interacao}. ` +
               `Nível de risco: ${interacao.nivel_risco}. ` +
               `Recomendação: ${interacao.recomendacao}`;
    });
    return explicacoes.join('\n');
}
document.addEventListener('DOMContentLoaded', function() {
    // 1. Verificar se a variável global com os dados existe (injeta no dashboard.php)
    if (typeof MARCACIONES_DATA === 'undefined' || !MARCACIONES_DATA.length) {
        // Se não houver dados, não inicializa o mapa
        console.log("Não há dados de marcações com coordenadas para exibir no mapa.");
        return;
    }

    // 2. Inicializar o Mapa Leaflet
    const mapaElement = document.getElementById('mini-mapa');
    if (!mapaElement) return; // Se o container não existe, para.

    // Definir as coordenadas centrais e o nível de zoom inicial (Ex: Lisboa)
    // Se quiser que o mapa se centre automaticamente, você pode calcular o centro médio.
    const centroPadrao = [38.7223, -9.1393]; 
    const zoomPadrao = 6; 

    const map = L.map('mini-mapa').setView(centroPadrao, zoomPadrao);

    // Adicionar o Tiled Layer (mapa base do OpenStreetMap)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    // 3. Adicionar Marcadores para cada Marcação
    let bounds = []; // Para ajustar o zoom do mapa

    MARCACIONES_DATA.forEach(marcacao => {
        // Verifica se existem coordenadas
        const lat = parseFloat(marcacao.latitude);
        const lon = parseFloat(marcacao.longitude);

        if (!isNaN(lat) && !isNaN(lon) && lat !== 0 && lon !== 0) {
            
            // Criar o conteúdo do popup
            const popupContent = `
                <div style="font-family: Arial;">
                    <strong>ID ${marcacao.id_marcacao}: ${marcacao.nome_cliente}</strong><br>
                    <span style="color: grey;">${marcacao.data_formatada}</span><br>
                    <hr style="margin: 5px 0;">
                    Morada: ${marcacao.morada_completa}<br>
                    <strong>Status: <span style="color: 
                        ${marcacao.status === 'Pendente' ? '#ffc107' : marcacao.status === 'Confirmada' ? '#28a745' : 'grey'};">
                        ${marcacao.status}
                    </span></strong>
                </div>
            `;

            // Adicionar o marcador ao mapa
            L.marker([lat, lon])
                .addTo(map)
                .bindPopup(popupContent);

            // Guardar as coordenadas para ajustar o zoom
            bounds.push([lat, lon]);
        }
    });

    // 4. Ajustar o Zoom e Centro do Mapa
    // Se existirem marcadores, ajusta o mapa para incluir todos eles.
    if (bounds.length > 0) {
        map.fitBounds(bounds, {padding: [50, 50]});
    }
});
<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed, watch, watchEffect } from 'vue';
import Chart from 'chart.js/auto'

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
    dados_pluviometros: Array,
});


const weather = ref({
    current_temperature: null,
    windspeed: null,
    condition: null,
    humidity: null,
});

const locationName = ref('');
const forecast = ref([]);
const loading = ref(true);
const error = ref(null);

const backgroundImages = {
    'céu limpo': '/images/clear-sky.jpg',
    'parcialmente nublado': '/images/partly-cloudy.jpg',
    'nublado': '/images/cloudy.jpeg',
    'coberto': '/images/overcast.jpg',
    'neblina': '/images/fog.jpg',
    'neblina com gelo': '/images/frost-fog.jpg',
    'chuvisco leve': '/images/drizzle.jpg',
    'chuvisco moderado': '/images/drizzle.jpg',
    'chuvisco denso': '/images/drizzle.jpg',
    'chuva leve': '/images/rain.jpg',
    'chuva moderada': '/images/rain.jpg',
    'chuva forte': '/images/rain.jpg',
    'chuva de pancadas leve': '/images/rain.jpg',
    'chuva de pancadas moderada': '/images/rain.jpg',
    'chuva de pancadas forte': '/images/rain.jpg',
    'tempestade com trovões': '/images/thunderstorm.jpg',
    // adicione mais mapeamentos conforme necessário
};

const weatherIcons = computed(() => {
    const isDay = weather.value.is_day;
    return {
        'céu limpo': isDay ? '/images/icons/sun.jpg' : '/images/icons/moon.png',
        'parcialmente nublado': '/images/icons/partly-cloudy.png',
        'nublado': '/images/icons/cloudy.png',
        'coberto': '/images/icons/overcast.png',
        'neblina': '/images/icons/fog.png',
        'neblina com gelo': '/images/icons/frost-fog.png',
        'chuvisco leve': '/images/icons/drizzle.png',
        'chuvisco moderado': '/images/icons/drizzle.png',
        'chuvisco denso': '/images/icons/drizzle.png',
        'chuva leve': '/images/icons/rain.png',
        'chuva moderada': '/images/icons/rain.png',
        'chuva forte': '/images/icons/rain.png',
        'chuva de pancadas leve': '/images/icons/rain.png',
        'chuva de pancadas moderada': '/images/icons/rain.png',
        'chuva de pancadas forte': '/images/icons/rain.png',
        'tempestade com trovões': '/images/icons/thunderstorm.png',
        // e por aí vai
    };
});

const backgroundStyle = computed(() => {
    if (loading.value || error.value || !weather.value.condition) {
        return {};
    }
    const key = weather.value.condition.toLowerCase();
    const url = backgroundImages[key] || '/images/weather/default.jpg';
    return {
        backgroundImage: `url(${url})`,
        backgroundSize: 'cover',
        backgroundPosition: 'center',
    };
});

function weatherCodeToCondition(code) {
    const map = {
        0: 'Céu limpo',
        1: 'Parcialmente nublado',
        2: 'Nublado',
        3: 'Coberto',
        45: 'Neblina',
        48: 'Neblina com gelo',
        51: 'Chuvisco leve',
        53: 'Chuvisco moderado',
        55: 'Chuvisco denso',
        61: 'Chuva leve',
        63: 'Chuva moderada',
        65: 'Chuva forte',
        80: 'Chuva de pancadas leve',
        81: 'Chuva de pancadas moderada',
        82: 'Chuva de pancadas forte',
        95: 'Tempestade com trovões',
    };
    return map[code] || 'Desconhecido';
}

function formatDate(dateStr) {
    const parts = dateStr.split('-');
    const fixedDate = new Date(parts[0], parts[1] - 1, parts[2]); // sem UTC
    return fixedDate.toLocaleDateString(undefined, { weekday: 'short', day: 'numeric', month: 'short' });
}


function formatTime(dateStr) {
    const d = new Date(dateStr);
    return d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
}

async function fetchLocationName(lat, lon) {
    try {
        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lon}&format=json`);
        const data = await res.json();
        locationName.value = data.address.city || data.address.town || data.address.village || data.address.county || 'Localização desconhecida';
    } catch {
        locationName.value = 'Localização desconhecida';
    }
}

async function fetchWeather() {
    loading.value = true;
    error.value = null;
    try {
        const position = await new Promise((resolve, reject) => {
            if (!navigator.geolocation) reject('Geolocalização não suportada');
            navigator.geolocation.getCurrentPosition(resolve, () => reject('Permissão negada para localização'));
        });
        const lat = position.coords.latitude.toFixed(4);
        const lon = position.coords.longitude.toFixed(4);
        await fetchLocationName(lat, lon);
        const url = `https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}` +
            `&daily=weathercode,temperature_2m_max,temperature_2m_min,sunrise,sunset,rain_sum` +
            `&hourly=temperature_2m,relative_humidity_2m,rain` +
            `&current=wind_direction_10m,wind_speed_10m,rain,temperature_2m,relative_humidity_2m,apparent_temperature,is_day` +
            `&forecast_hours=1&past_hours=24&timezone=auto`;
        const res = await fetch(url);
        const data = await res.json();
        if (!data.current || !data.daily) throw new Error('Dados do tempo indisponíveis');
        weather.value.current_temperature = data.current.temperature_2m;
        weather.value.windspeed = data.current.wind_speed_10m;
        weather.value.humidity = data.current.relative_humidity_2m;
        weather.value.condition = weatherCodeToCondition(data.daily.weathercode[0]);
        forecast.value = data.daily.time.slice(0, 7).map((date, i) => ({
            date: formatDate(date),
            temp_max: data.daily.temperature_2m_max[i],
            temp_min: data.daily.temperature_2m_min[i],
            condition: weatherCodeToCondition(data.daily.weathercode[i]),
            sunrise: data.daily.sunrise[i],
            sunset: data.daily.sunset[i],
        }));
    } catch (err) {
        error.value = err.message || 'Erro ao buscar dados do tempo';
    } finally {
        loading.value = false;
    }
}

//GRÁFICO
const cidades = ref([]);
const cidadeSelecionada = ref('')
let chartInstance = null

watchEffect(() => {
    cidades.value = props.dados_pluviometros.map(p => {
        const dadosSensor = p.dados?.slice() ?? [];

        console.log(dadosSensor);
        const tempFallback = weather.value.current_temperature ?? 22;
        const umidadeFallback = weather.value.humidity ?? 50;
        const chuvaFallback = weather.value.rain ?? 0;

        const temperaturas = [];
        const umidades = [];
        const chuvas = [];
        const labels = [];

        const n = dadosSensor.length; // pega tudo que tem, ou define um limite aqui

        for (let i = 0; i < n; i++) {
            const dado = dadosSensor[i];
            temperaturas.push(dado?.temperatura ? parseFloat(dado.temperatura) : tempFallback);
            umidades.push(dado?.umidade ? parseFloat(dado.umidade) : umidadeFallback);
            chuvas.push(dado?.chuva ? parseFloat(dado.chuva) : chuvaFallback);
            labels.push(dado?.data_hora ? formatTime(dado.data_hora) : `--:--`);
        }

        return {
            nome: `${p.cidade ?? 'Cidade'} - ${p.endereco ?? 'Endereço'}`,
            temp: temperaturas,
            umidade: umidades,
            chuva: chuvas,
            labels: labels
        };
    });

    if (cidades.value.length > 0 && !cidadeSelecionada.value) {
        cidadeSelecionada.value = cidades.value[0].nome;
    }
});


function criarGrafico(dataTemp, dataUmid, dataChuva, labels) {
    // Quebra a reatividade com .slice()
    const tempData = dataTemp.slice();
    const umidData = dataUmid.slice();
    const chuvaData = dataChuva.slice();

    console.log('Temperaturas:', tempData);
    console.log('Umidades:', umidData);
    console.log('Chuvas:', chuvaData);
    const ctx = document.getElementById('cidadeChart').getContext('2d');
    if (chartInstance) chartInstance.destroy();

    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: `Temperatura (°C) - ${cidadeSelecionada.value}`,
                    data: dataTemp,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgb(54, 162, 235)',
                    borderWidth: 1,
                    yAxisID: 'y',
                },
                {
                    label: `Umidade (%) - ${cidadeSelecionada.value}`,
                    data: dataUmid,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgb(75, 192, 192)',
                    borderWidth: 1,
                    yAxisID: 'y1',
                },
                {
                    label: `Chuva (mm) - ${cidadeSelecionada.value}`,
                    data: dataChuva,
                    backgroundColor: 'rgba(255, 159, 64, 0.6)',
                    borderColor: 'rgb(255, 159, 64)',
                    borderWidth: 1,
                    yAxisID: 'y2',
                },
            ],
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    type: 'linear',
                    position: 'left',
                    beginAtZero: true,
                    title: { display: true, text: 'Temperatura (°C)' },
                },
                y1: {
                    type: 'linear',
                    position: 'right',
                    beginAtZero: true,
                    title: { display: true, text: 'Umidade (%)' },
                },
                y2: {
                    type: 'linear',
                    position: 'right',
                    beginAtZero: true,
                    offset: true,
                    title: { display: true, text: 'Chuva (mm)' },
                }
            }
        },
    });
}

watch(cidadeSelecionada, (novaCidade) => {
    const cidade = cidades.value.find(c => c.nome === novaCidade);
    if (cidade) criarGrafico(cidade.temp, cidade.umidade, cidade.chuva, cidade.labels);
});

//QUANDO MONTA
onMounted(() => {
    fetchWeather();
    const cidade = cidades.value.find(c => c.nome === cidadeSelecionada.value);
    console.log('Montou, cidade selecionada:', cidadeSelecionada.value);
    console.log('Dados da cidade no mount:', cidade);
    if (cidade) criarGrafico(cidade.temp, cidade.umidade, cidade.chuva, cidade.labels);
});


</script>

<template>

    <Head title="Welcome" />

    <div class="bg-light text-secondary min-vh-100 d-flex flex-column justify-content-center align-items-center position-relative px-3 py-4"
        :style="backgroundStyle">
        <!-- Navegação no canto superior direito fixa -->
        <nav class="d-flex gap-2 p-3"
            style="position: fixed; top: 0; right: 0; left: 0; width: 100%; background-color: #000; z-index: 1050;">
            <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="btn btn-outline-light btn-sm">
            Dashboard
            </Link>

            <template v-else>
                <Link :href="route('login')" class="btn btn-outline-light btn-sm">
                Log in
                </Link>
                <Link v-if="canRegister" :href="route('register')" class="btn btn-outline-light btn-sm">
                Register
                </Link>
            </template>
        </nav>

        <!-- Espaçador para o nav fixo -->
        <div style="height: 56px;"></div>

        <!-- Previsão do tempo ocupando toda largura abaixo do login/register -->
        <div class="row w-100 gx-3 gy-3 justify-content-center">
            <!-- Tempo Atual -->
            <div class="col-12 col-md-auto">
                <div class="card w-100" style="min-width: 180px; min-height: 140px;">
                    <div class="card-header p-2 text-center">
                        <small class="mb-0">Tempo agora</small>
                    </div>
                    <div class="card-body p-2 text-center">
                        <div v-if="loading" class="my-2">Carregando...</div>
                        <div v-else-if="error" class="text-danger my-2">{{ error }}</div>
                        <div v-else>
                            <p class="mb-1 small text-muted">{{ locationName }}</p>
                            <p class="mb-1"><strong class="fs-4">{{ weather.current_temperature }}°C</strong></p>
                            <div class="mb-1 d-flex align-items-center justify-content-center gap-2">
                                <img v-if="weatherIcons[weather.condition?.toLowerCase()]"
                                    :src="weatherIcons[weather.condition?.toLowerCase()]" alt="ícone" width="24"
                                    height="24">
                            </div>
                            <span class="small">{{ weather.condition }}</span>
                            <p class="mb-1 small">Vento {{ weather.windspeed }} km/h</p>
                            <p class="mb-0 small">Umidade {{ weather.humidity }}%</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Previsão 5 dias -->
            <div class="col-12 col-md flex-grow-1">
                <div class="card w-100" style="min-height: 140px;">
                    <div class="card-header p-2 text-center">
                        <small class="mb-0">Previsão dos próximos 5 dias</small>
                    </div>
                    <div class="card-body p-3">
                        <div class="d-flex gap-2 justify-content-start align-items-stretch overflow-auto"
                            style="white-space: nowrap;">
                            <template v-if="!loading && !error">
                                <div v-for="(day, index) in forecast" :key="index"
                                    class="text-center p-2 d-flex flex-column align-items-center bg-light rounded"
                                    style="min-width: 120px; font-size: 0.8rem; flex-shrink: 0;">
                                    <p class="mb-1 small fw-semibold">{{ day.date }}</p>
                                    <div class="mb-1 d-flex flex-column align-items-center" title="Condição">
                                        <img v-if="weatherIcons[day.condition?.toLowerCase()]"
                                            :src="weatherIcons[day.condition?.toLowerCase()]" alt="ícone" width="24"
                                            height="24">
                                        <span>{{ day.condition }}</span>
                                    </div>
                                    <p class="mb-1" title="Máx"><strong>{{ day.temp_max }}°C</strong></p>
                                    <p class="mb-0 small text-muted" title="Mín">{{ day.temp_min }}°C</p>
                                    <p class="mb-0 small text-muted" title="Nascer do sol">🌅 {{ formatTime(day.sunrise)
                                    }}</p>
                                    <p class="mb-0 small text-muted" title="Pôr do sol">🌇 {{ formatTime(day.sunset) }}
                                    </p>
                                </div>
                            </template>
                            <div v-else class="text-center w-100">Carregando previsão...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bloco com cidades e gráfico -->
        <div class="container mt-4">
  <div class="row gy-3">
    <!-- Lista cidades -->
    <div class="col-12 col-md-4">
      <div class="list-group overflow-auto" style="max-height: 200px;">
        <button
          v-for="cidade in cidades"
          :key="cidade.nome"
          @click="cidadeSelecionada = cidade.nome"
          :class="[
            'list-group-item',
            'list-group-item-action',
            { active: cidadeSelecionada === cidade.nome }
          ]"
          style="cursor: pointer;"
        >
          {{ cidade.nome }}
        </button>
      </div>
    </div>

    <!-- Gráfico -->
    <div class="col-12 col-md-8">
      <div class="bg-light rounded p-3">
        <canvas id="cidadeChart" style="height: 30%"></canvas>
      </div>
    </div>
  </div>
</div>


    </div>
</template>

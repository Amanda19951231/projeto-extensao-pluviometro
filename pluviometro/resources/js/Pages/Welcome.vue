<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue'
import Chart from 'chart.js/auto'
import * as L from 'leaflet'
import axios from 'axios'

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
    dados_pluviometros: Array,
});

const raindrops = ref([])
for (let i = 0; i < 100; i++) {
    raindrops.value.push({
        left: Math.random() * 100,
        duration: 0.4 + Math.random() * 0.6,
        delay: Math.random()
    })
}

const cards = ref([
    { id: 1, title: 'Chuva acumulada hoje', value: '— mm', icon: '🌧️', sub: 'Última medição: —' },
    { id: 2, title: 'Acumulado do mês', value: '— mm', icon: '📅', sub: 'Média histórica: — mm' },
    { id: 3, title: 'Intensidade máxima', value: '— mm/h', icon: '⛈️', sub: 'Hora: —' },
    { id: 4, title: 'Dias de chuva no mês', value: '—', icon: '💧', sub: 'Leve/mod/forte' },
])

const pluviometros = ref([])

// filtros com v-model
const filterDate = ref('')         // binding com <input type="date" v-model="filterDate">
const filterLocation = ref('all')  // <select v-model="filterLocation">
const filterType = ref('all')      // <select v-model="filterType">
const filterRange = ref('month')   // <select v-model="filterRange">

// coords do usuário (se conseguir)
const userCoords = ref(null)

// helpers
const pad = (n) => String(n).padStart(2, '0')
const parseDateLocal = (s) => {
    if (!s) return null
    let d = new Date(s)
    if (isNaN(d)) d = new Date(s.replace(' ', 'T'))
    if (isNaN(d)) return null
    return d
}
const formatLocalKey = (date, granularity = 'day') => {
    if (!date) return ''
    const Y = date.getFullYear()
    const M = pad(date.getMonth() + 1)
    const D = pad(date.getDate())
    const H = pad(date.getHours())
    const m = pad(date.getMinutes())

    if (granularity === 'day') return `${D}/${M}/${Y}`
    if (granularity === 'hour') return `${D}/${M}/${Y} ${H}:00`
    if (granularity === 'minute') return `${D}/${M}/${Y} ${H}:${m}`
    if (granularity === 'month') return `${M}/${Y}`

    return `${D}/${M}/${Y}`
}

// agrega genérico
const aggregateBy = (items, granularity = 'day', thresholds = { leve: 2, moderada: 10 }) => {
    const map = new Map()
    for (const p of items) {
        const d = parseDateLocal(p.data_hora)
        if (!d) continue
        const key = formatLocalKey(d, granularity)
        const val = parseFloat(p.chuva) || 0
        if (!map.has(key)) map.set(key, { chuva: 0, count: 0, leve: 0, moderada: 0, forte: 0 })
        const entry = map.get(key)
        entry.chuva += val
        entry.count += 1
        if (val < thresholds.leve) entry.leve += 1
        else if (val < thresholds.moderada) entry.moderada += 1
        else entry.forte += 1
    }
    const keys = Array.from(map.keys()).sort((a, b) => {
        const da = new Date(a.split('/').reverse().join('-'))
        const db = new Date(b.split('/').reverse().join('-'))
        return da - db
    })


    const labels = keys
    const rainData = keys.map(k => parseFloat(map.get(k).chuva.toFixed(2)))
    const intensitySeries = {
        leve: keys.map(k => map.get(k).leve),
        moderada: keys.map(k => map.get(k).moderada),
        forte: keys.map(k => map.get(k).forte)
    }
    return { labels, rainData, intensitySeries, map }
}

const destroyCharts = () => {
    if (!window.charts) window.charts = {}
    Object.values(window.charts).forEach(c => { try { c.destroy() } catch (e) { } })
    window.charts = {}
}

// Filtra dados brutos antes de agregar (date, location, type)
const getFilteredData = (dados) => {
    return dados.filter(d => {
        // local
        if (filterLocation.value !== 'all') {
            const loc = (d.numero_serie || d.station_id || d.local_id || d.nome || '').toString().toLowerCase()
            if (!loc.includes(filterLocation.value.toString().toLowerCase())) return false
        }
        // tipo (intensidade)
        if (filterType.value !== 'all') {
            const val = parseFloat(d.chuva) || 0
            if (filterType.value === 'leve' && val >= 2) return false
            if (filterType.value === 'moderada' && (val < 2 || val >= 10)) return false
            if (filterType.value === 'forte' && val < 10) return false
        }
        // date -> se o usuário escolheu uma data específica
        if (filterDate.value) {
            const sel = new Date(filterDate.value)
            const ddate = parseDateLocal(d.data_hora)
            if (!ddate) return false
            if (ddate.getFullYear() !== sel.getFullYear() || ddate.getMonth() !== sel.getMonth() || ddate.getDate() !== sel.getDate()) return false
        }
        return true
    })
}

// calcula estatísticas do mês selecionado
const computeMonthStats = (dados, referenceDate = new Date(), thresholds = { leve: 2, moderada: 10 }) => {
    const ref = filterDate.value ? new Date(filterDate.value) : referenceDate
    const month = ref.getMonth()
    const year = ref.getFullYear()
    let monthSum = 0
    const daysSet = new Set()
    let maxRecord = null
    const intensityCounts = { leve: 0, moderada: 0, forte: 0 }

    for (const p of dados) {
        const d = parseDateLocal(p.data_hora)
        if (!d) continue
        if (d.getFullYear() !== year || d.getMonth() !== month) continue
        const val = parseFloat(p.chuva) || 0
        monthSum += val
        if (val > 0) daysSet.add(formatLocalKey(d, 'day'))
        if (val < thresholds.leve) intensityCounts.leve++
        else if (val < thresholds.moderada) intensityCounts.moderada++
        else intensityCounts.forte++
        if (!maxRecord || val > (parseFloat(maxRecord.chuva) || 0)) {
            maxRecord = { ...p, chuva: val, data: d }
        }
    }

    return {
        monthSum: parseFloat(monthSum.toFixed(2)),
        daysOfRain: daysSet.size,
        intensityCounts,
        maxRecord
    }
}

// pega localização do usuário (promessa com timeout)
const getUserLocation = (timeout = 8000) => {
    return new Promise((resolve) => {
        if (!navigator.geolocation) return resolve(null)
        let resolved = false
        const timer = setTimeout(() => { if (!resolved) { resolved = true; resolve(null) } }, timeout)
        navigator.geolocation.getCurrentPosition(pos => {
            if (resolved) return
            resolved = true
            clearTimeout(timer)
            userCoords.value = { lat: pos.coords.latitude, lon: pos.coords.longitude }
            resolve(userCoords.value)
        }, () => {
            if (resolved) return
            resolved = true
            clearTimeout(timer)
            resolve(null)
        }, { enableHighAccuracy: true, maximumAge: 1000 * 60 * 5 })
    })
}

// haversine
const distanceKm = (lat1, lon1, lat2, lon2) => {
    const toRad = v => v * Math.PI / 180
    const R = 6371
    const dLat = toRad(lat2 - lat1)
    const dLon = toRad(lon2 - lon1)
    const a = Math.sin(dLat / 2) ** 2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) ** 2
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))
}

// seleciona a estação mais próxima (tentativa geolocalização -> fallback: estação mais recente)
const pickNearestStation = async (dados) => {
    if (!dados || dados.length === 0) return null
    // tenta obter coords do user
    let coords = userCoords.value
    if (!coords) coords = await getUserLocation()
    if (coords) {
        let best = null
        let bestDist = Infinity
        for (const p of dados) {
            const lat = parseFloat(p.latitude)
            const lon = parseFloat(p.longitude)
            if (isNaN(lat) || isNaN(lon)) continue
            const d = distanceKm(coords.lat, coords.lon, lat, lon)
            if (d < bestDist) { bestDist = d; best = p }
        }
        if (best) {
            filterLocation.value = best.numero_serie || best.station_id || filterLocation.value
            return best
        }
    }
    // fallback: pega estação com última medição (mais recente)
    const sorted = [...dados].sort((a, b) => (parseDateLocal(b.data_hora) || 0) - (parseDateLocal(a.data_hora) || 0))
    if (sorted.length > 0) {
        const s = sorted[0]
        filterLocation.value = s.numero_serie || s.station_id || filterLocation.value
        return s
    }
    return null
}

// função principal
const fetchData = async (granularity = 'day') => {
    try {
        const res = await axios.get('/pluviometros/dados')
        const dados = res.data.data || []
        pluviometros.value = dados

        // se o usuário não escolheu local, tenta pegar o mais próximo ao carregar
        if (!filterLocation.value || filterLocation.value === 'all') {
            await pickNearestStation(dados)
        }

        // aplica filtros antes das agregações
        const filtered = getFilteredData(dados)

        // última medição (do conjunto filtrado)
        const sortedByDateDesc = [...filtered].sort((a, b) => (parseDateLocal(b.data_hora) || 0) - (parseDateLocal(a.data_hora) || 0))
        const ultima = sortedByDateDesc[0] || null
        const ultimaTxt = ultima ? (formatLocalKey(parseDateLocal(ultima.data_hora) || new Date(ultima.data_hora), 'minute')) : '—'

        // Acumulado do dia
        const aggDay = aggregateBy(filtered, 'day')
        const todayKey = formatLocalKey(new Date(), 'day')
        const hojeAcumulado = aggDay.map.get(todayKey)?.chuva || 0

        // Estatísticas do mês
        const monthStats = computeMonthStats(filtered, filterDate.value ? new Date(filterDate.value) : new Date())
        const intensidadeMax = monthStats.maxRecord ? `${(monthStats.maxRecord.chuva).toFixed(2)} mm` : '—'
        const intensidadeHora = monthStats.maxRecord ? (formatLocalKey(parseDateLocal(monthStats.maxRecord.data_hora) || new Date(monthStats.maxRecord.data_hora), 'minute')) : '—'
        const acumuladoMesTxt = `${monthStats.monthSum.toFixed(2)} mm`
        const diasChuvaTxt = `${monthStats.daysOfRain} (L:${monthStats.intensityCounts.leve} / M:${monthStats.intensityCounts.moderada} / F:${monthStats.intensityCounts.forte})`

        // atualiza cards
        cards.value = [
            { id: 1, title: 'Chuva acumulada hoje', value: `${hojeAcumulado.toFixed(2)} mm`, icon: '🌧️', sub: `Última medição: ${ultimaTxt}` },
            { id: 2, title: 'Acumulado do mês', value: acumuladoMesTxt, icon: '📅', sub: `Média histórica: — mm` },
            { id: 3, title: 'Intensidade máxima', value: intensidadeMax, icon: '⛈️', sub: `Hora: ${intensidadeHora}` },
            { id: 4, title: 'Dias de chuva no mês', value: `${monthStats.daysOfRain}`, icon: '💧', sub: diasChuvaTxt },
        ]

        // agregação para gráficos (usa o conjunto filtrado)
        let { labels, rainData, intensitySeries } = aggregateBy(filtered, granularity)

        // destrói charts antigos
        destroyCharts()
        window.charts = {}

        // cria charts
        window.charts.bar = new Chart(document.getElementById('barChart'), {
            type: 'bar',
            data: { labels, datasets: [{ label: 'Chuva (mm)', data: rainData, backgroundColor: '#0d6efd' }] },
            options: { responsive: true, plugins: { legend: { display: false } } }
        })
        window.charts.line = new Chart(document.getElementById('lineChart'), {
            type: 'line',
            smooth: true,
            data: { labels, datasets: [{ label: 'Chuva (mm)', data: rainData, borderColor: '#0d6efd', fill: true, tension: 0.4 }] }
        })
        window.charts.area = new Chart(document.getElementById('areaChart'), {
            type: 'line',
            smooth: true,
            data: { labels, datasets: [{ label: 'Acúmulo', data: rainData, borderColor: '#0d6efd', backgroundColor: 'rgba(13,110,253,0.3)', fill: true, tension: 0.4 }] }
        })
        window.charts.stacked = new Chart(document.getElementById('stackedBarChart'), {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    { label: 'Leve', data: intensitySeries.leve, backgroundColor: '#0dcaf0' },
                    { label: 'Moderada', data: intensitySeries.moderada, backgroundColor: '#0d6efd' },
                    { label: 'Forte', data: intensitySeries.forte, backgroundColor: '#6610f2' }
                ]
            },
            options: { responsive: true, plugins: { tooltip: { mode: 'index', intersect: false } }, scales: { x: { stacked: true }, y: { stacked: true } } }
        })

        // scatter
        const scatterPoints = labels.map((l, i) => ({ x: i, y: rainData[i] }))
        window.charts.scatter = new Chart(document.getElementById('scatterChart'), {
            type: 'scatter',
            data: { datasets: [{ label: 'Chuva', data: scatterPoints, backgroundColor: '#0d6efd' }] },
            options: { scales: { x: { title: { display: true, text: 'Index/Hora' } }, y: { title: { display: true, text: 'mm' } } } }
        })

        // Leaflet (recria e centraliza na estação selecionada quando possível)
        try {
            const map = L.map('map').setView([-23.5, -46.6], 10)
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map)

            // se filterLocation é uma estação específica, centraliza nela
            let centerSet = false
            if (filterLocation.value && filterLocation.value !== 'all') {
                const found = pluviometros.value.find(p => (p.numero_serie || '').toString() === filterLocation.value.toString())
                if (found && found.latitude && found.longitude) {
                    map.setView([found.latitude, found.longitude], 12)
                    L.circle([found.latitude, found.longitude], { radius: 200, color: '#ffdd57', fillOpacity: 0.4 }).addTo(map)
                    centerSet = true
                }
            }

            pluviometros.value.forEach(p => {
                const radius = (parseFloat(p.chuva) || 0) * 100
                L.circle([p.latitude, p.longitude], { radius: Math.max(100, radius), color: '#0d6efd', fillOpacity: 0.5 }).addTo(map)
            })
        } catch (e) { /* ignora se já tiver mapa */ }

    } catch (err) {
        console.error(err)
    }
}

// Aplica filtros -> chama fetchData com range atual
const applyFilters = () => {
    const gran = filterRange.value === 'day' ? 'hour' : (filterRange.value === 'week' ? 'day' : 'day')
    fetchData(gran)
}

const uniquePluviometros = computed(() => {
    const map = new Map()
    pluviometros.value.forEach(p => {
        if (!map.has(p.numero_serie)) {
            map.set(p.numero_serie, p)
        }
    })
    return Array.from(map.values())
})

// inicia: tenta pegar a estação mais próxima e carregar os dados
onMounted(() => {
    fetchData('day')
})
</script>


<template>

    <Head title="Sistema de Monitoramento de Chuvas" />

    <div class="sky">
        <div class="cloud c1"></div>
        <div class="cloud c2"></div>
        <div class="cloud c3"></div>
        <div class="cloud c4"></div>
    </div>

    <div class="rain">
        <div v-for="(drop, index) in raindrops" :key="index" class="raindrop" :style="{
            left: drop.left + '%',
            animationDuration: drop.duration + 's',
            animationDelay: drop.delay + 's'
        }"></div>
    </div>

    <div class="rain-wrapper text-secondary d-flex flex-column justify-content-center align-items-center position-relative px-3"
        style="padding-top: 70px;">
        <nav class="bg-light d-flex gap-2 p-3 align-items-center"
            style="position: fixed; top: 0; width: 100%; background-color: #000; z-index: 1050;">
            <img src="/images/logo2.png" alt="Logo" style="height: 30px;">
            <div class="ms-auto d-flex gap-2">
                <Link v-if="$page.props.auth.user" :href="route('dashboard')"
                    class="bg-dark btn btn-outline-light btn-sm">
                Dashboard
                </Link>
                <template v-else>
                    <Link :href="route('login')" class="bg-dark btn btn-outline-light btn-sm">
                    Log in
                    </Link>
                    <Link v-if="canRegister" :href="route('register')" class="bg-dark btn btn-outline-light btn-sm"
                        hidden>
                    Register
                    </Link>
                </template>
            </div>
        </nav>
    </div>
    <!-- Filtros -->
    <section class="p-4 border-bottom">
        <form id="filtersForm" class="row g-3 align-items-center">
            <div class="col-auto">
                <label for="filterDate" class="form-label small text-light">Data</label>
                <input id="filterDate" type="date" v-model="filterDate"
                    class="form-control form-control-sm bg-dark text-white" />
            </div>

            <div class="col-auto">
                <label for="filterLocation" class="form-label small text-light">Local</label>
                <select id="filterLocation" v-model="filterLocation"
                    class="form-select form-select-sm bg-dark text-white">
                    <option value="all">Todas as estações</option>
                    <option v-for="p in uniquePluviometros" :key="p.numero_serie" :value="p.numero_serie">
                        {{ p.numero_serie }} - {{ p.cidade }}
                    </option>
                </select>
            </div>


            <div class="col-auto">
                <label for="filterType" class="form-label small text-light">Tipo (intensidade)</label>
                <select id="filterType" v-model="filterType" class="form-select form-select-sm bg-dark text-white">
                    <option value="all">Todas</option>
                    <option value="leve">Leve</option>
                    <option value="moderada">Moderada</option>
                    <option value="forte">Forte</option>
                </select>
            </div>

            <div class="col-auto">
                <label for="filterRange" class="form-label small text-light">Período</label>
                <select id="filterRange" v-model="filterRange" class="form-select form-select-sm bg-dark text-white">
                    <option value="day">Dia</option>
                    <option value="week">Semana</option>
                    <option value="month">Mês</option>
                </select>
            </div>

            <div class="col-auto d-flex align-items-end">
                <button type="button" id="btnApply" class="btn btn-primary btn-sm" @click="applyFilters">
                    Aplicar
                </button>
            </div>
        </form>
    </section>

    <!-- Cards resumo -->
    <section class="p-4">
        <div class="row g-3">
            <div class="col-12 col-sm-6 col-lg-3" v-for="card in cards" :key="card.id">
                <div class="card text-white bg-dark h-100">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <p class="small text-secondary">{{ card.title }}</p>
                            <p class="h4 fw-bold mt-1">{{ card.value }}</p>
                        </div>
                        <div class="fs-2">{{ card.icon }}</div>
                    </div>
                    <div class="card-footer small text-secondary">
                        {{ card.sub }}
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Quantidade de chuva -->
    <section class="mb-5 mx-5">
        <h5 class="text-light text-center mb-5">Quantidade de Chuva (mm)</h5>
        <div class="row g-3">
            <div class="col-md-4">
                <canvas id="barChart"></canvas>
            </div>
            <div class="col-md-4">
                <canvas id="lineChart"></canvas>
            </div>
            <div class="col-md-4 mt-3">
                <canvas id="areaChart"></canvas>
            </div>
        </div>
    </section>

    <!-- Intensidade da chuva -->
    <section class="mb-5 mx-5">
        <h5 class="text-light text-center mb-5">Intensidade da Chuva</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <canvas id="stackedBarChart"></canvas>
            </div>
            <div class="col-md-6 mt-3">
                <canvas id="scatterChart"></canvas>
            </div>
        </div>
    </section>
</template>

<style>
html,
body {
    margin: 0;
    padding: 0;
    width: 100%;
    min-height: 100%;
    background: #0a0a0a;
}

/* Ajusta a chuva e nuvens pra não bloquear o scroll 
.sky,
.rain {
    position: fixed;*/
/* fixa no fundo da tela 
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 0;*/
/* atrás de todo o resto 
}*/

/*.raindrop {
    position: absolute;
    top: -10px;
    width: 2px;
    height: 10px;
    background: rgba(58, 57, 57, 0.5);
    animation: fall linear infinite;
}

@keyframes fall {
    0% {
        transform: translateY(-10px);
    }

    100% {
        transform: translateY(100vh);
    }
}

:root {
    --cloud-color: rgba(255, 255, 255, 0.12);
    --cloud-blur: 24px;
}

.sky {
    position: absolute;
    width: 100%;
    height: 100%;
    overflow: hidden;
    pointer-events: none;
}

.cloud {
    position: absolute;
    background: var(--cloud-color);
    border-radius: 50px;
    filter: blur(var(--cloud-blur));
    mix-blend-mode: screen;
    will-change: transform;
}

.cloud::before,
.cloud::after {
    content: "";
    position: absolute;
    background: inherit;
    border-radius: 50%;
    top: -30%;
    width: 60%;
    height: 140%;
    left: 10%;
    filter: blur(calc(var(--cloud-blur) * 0.8));
} */

/* clouds positions */
/* .c1 {
    width: 520px;
    height: 140px;
    top: 8%;
    left: -30%;
    opacity: 0.95;
    animation: floatRight 60s linear infinite;
}

.c2 {
    width: 380px;
    height: 110px;
    top: 28%;
    left: -40%;
    opacity: 0.7;
    animation: floatRight 90s linear infinite -10s;
}

.c3 {
    width: 680px;
    height: 180px;
    top: 55%;
    left: -50%;
    opacity: 0.6;
    animation: floatRight 120s linear infinite -30s;
}

.c4 {
    width: 240px;
    height: 80px;
    top: 75%;
    left: -20%;
    opacity: 0.5;
    animation: floatRight 45s linear infinite -5s;
}

@keyframes floatRight {
    0% {
        transform: translateX(-10vw);
    }

    100% {
        transform: translateX(130vw);
    }
}

@media (max-width:700px) {
    :root {
        --cloud-blur: 18px;
    }

    .c1,
    .c3 {
        display: none;
    }
}*/
</style>

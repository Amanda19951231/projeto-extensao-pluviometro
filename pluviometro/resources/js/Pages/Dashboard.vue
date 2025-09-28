<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Props
const props = defineProps({
  pluviometros: Array
});

// Filtros
const selectedStates = ref([]);
const selectedRegions = ref([]);

// Estado -> Região
const estadoParaRegiao = {
  'RS': 'sul','SC': 'sul','PR': 'sul',
  'GO': 'centro-oeste','MT': 'centro-oeste','MS': 'centro-oeste','DF': 'centro-oeste',
  'AM': 'norte','PA': 'norte','RO': 'norte','RR': 'norte','AC': 'norte','AP': 'norte','TO': 'norte',
  'MA': 'nordeste','PI': 'nordeste','CE': 'nordeste','RN': 'nordeste','PB': 'nordeste','PE': 'nordeste','AL': 'nordeste','SE': 'nordeste','BA': 'nordeste',
  'SP': 'sudeste','RJ': 'sudeste','ES': 'sudeste','MG': 'sudeste'
};

let map;
let markersLayer;

// Força Leaflet a usar apenas seus ícones
delete L.Icon.Default.prototype._getIconUrl;

// Ícone custom
const greenIcon = L.icon({
  iconUrl: '/leaflet/marker-icon-2x-blue.png',  // mover pra public/leaflet/
  shadowUrl: '/leaflet/marker-shadow.png',
  iconSize: [25, 41],
  iconAnchor: [12, 41],
  popupAnchor: [1, -34],
  shadowSize: [41, 41]
});

// Atualiza marcadores conforme filtros
function updateMarkers() {
  markersLayer.clearLayers();
  props.pluviometros
    .filter(p => {
      if (selectedStates.value.length && !selectedStates.value.includes(p.estado)) return false;
      const reg = estadoParaRegiao[p.estado];
      if (selectedRegions.value.length && !selectedRegions.value.includes(reg)) return false;
      return p.latitude && p.longitude;
    })
    .forEach(p => {
      L.marker([p.latitude, p.longitude], { icon: greenIcon })
        .addTo(markersLayer)
        .bindPopup(
          `<b>${p.numero_serie}</b><br>` +
          `<b>${p.nome}</b><br>` +
          `<b>${p.endereco}, ${p.numero}</b><br>` +
          `${p.cidade} - ${p.cep} - ${p.estado}`
        );
    });
}

// Inicializa mapa
onMounted(() => {
  map = L.map('map').setView([-15.7801, -47.9292], 4);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
  }).addTo(map);

  markersLayer = L.layerGroup().addTo(map);
  updateMarkers();
});

// Observa filtros
watch([selectedStates, selectedRegions], updateMarkers);
</script>

<template>

  <Head title="Dashboard" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl mt-3 mb-3 font-semibold leading-tight text-gray-800 text-center">
        Nossos pluviômetros
      </h2>
    </template>

    <div class="py-4">
      <div class="container-fluid">
        <div class="row gx-4">

          <!-- Filtros -->
          <aside class="col-md-4 bg-white p-4 rounded shadow border">
            <!-- Filtro de Estado -->
            <div class="mb-4">
              <h5 class="mb-3">Estado</h5>
              <select id="estado-select" multiple class="form-select" v-model="selectedStates"
                aria-label="Seleção múltipla de estados" style="height: 150px;">
                <option value="EX">Exterior</option>
                <option value="AC">AC</option>
                <option value="AL">AL</option>
                <option value="AP">AP</option>
                <option value="AM">AM</option>
                <option value="BA">BA</option>
                <option value="CE">CE</option>
                <option value="DF">DF</option>
                <option value="ES">ES</option>
                <option value="GO">GO</option>
                <option value="MA">MA</option>
                <option value="MT">MT</option>
                <option value="MS">MS</option>
                <option value="MG">MG</option>
                <option value="PA">PA</option>
                <option value="PB">PB</option>
                <option value="PR">PR</option>
                <option value="PE">PE</option>
                <option value="PI">PI</option>
                <option value="RJ">RJ</option>
                <option value="RN">RN</option>
                <option value="RS">RS</option>
                <option value="RO">RO</option>
                <option value="RR">RR</option>
                <option value="SC">SC</option>
                <option value="SP">SP</option>
                <option value="SE">SE</option>
                <option value="TO">TO</option>
              </select>
              <small class="form-text text-muted">
                Pressione Ctrl (ou Cmd) para múltipla seleção
              </small>
            </div>

            <!-- Filtro de Região -->
            <div>
              <h5 class="mb-3">Região</h5>
              <div class="d-flex flex-column gap-2">
                <div class="form-check" v-for="reg in ['sul', 'centro-oeste', 'norte', 'nordeste', 'sudeste']" :key="reg">
                  <input class="form-check-input" type="checkbox" :value="reg" v-model="selectedRegions"
                    :id="`regiao-${reg}`" />
                  <label class="form-check-label" :for="`regiao-${reg}`">
                    {{ reg.charAt(0).toUpperCase() + reg.slice(1).replace('-', ' ') }}
                  </label>
                </div>
              </div>
            </div>
          </aside>

          <!-- Mapa -->
          <div class="col-md-8">
            <div id="map" class="rounded shadow" style="height: 680px;"></div>
          </div>

        </div>
      </div>
    </div>

  </AuthenticatedLayout>
</template>

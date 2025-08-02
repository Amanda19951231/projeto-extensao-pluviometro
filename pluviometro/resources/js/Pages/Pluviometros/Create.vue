<script setup>
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
  pluviometro: {
    type: Object,
    default: null,
  },
});

const estados = [
  'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO',
  'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI',
  'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
];

const form = useForm({
  nome: props.pluviometro?.nome || '',
  codigo: props.pluviometro?.numero_serie || '',
  latitude: props.pluviometro?.latitude || '',
  longitude: props.pluviometro?.longitude || '',
  cidade: props.pluviometro?.cidade || '',
  estado: props.pluviometro?.estado || '',
  endereco: props.pluviometro?.endereco || '',
  numero: props.pluviometro?.numero || '',
  cep: props.pluviometro?.cep || '',
});

const isEdit = !!props.pluviometro;

const submit = () => {
  if (isEdit) {
    form.put(route('pluviometros.update', props.pluviometro.id_pluviometro));
  } else {
    form.post(route('pluviometros.store'));
  }
};
</script>


<template>
  <Head :title="isEdit ? 'Editar pluviômetro' : 'Novo pluviômetro'" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800 text-center">
        {{ isEdit ? 'Editar pluviômetro' : 'Novo pluviômetro' }}
      </h2>
    </template>

    <div class="container py-4">
      <div class="row justify-content-center">
        <div class="col-md-10">
          <div class="card shadow-sm">
            <div class="card-body">
              <form @submit.prevent="submit">
                <div class="row g-3">

                  <div class="col-md-6">
                    <label for="nome" class="form-label">Nome</label>
                    <input v-model="form.nome" type="text" class="form-control" id="nome" />
                    <div v-if="form.errors.nome" class="text-danger mt-1">{{ form.errors.nome }}</div>
                  </div>

                  <div class="col-md-6">
                    <label for="codigo" class="form-label">Código</label>
                    <input v-model="form.codigo" type="text" class="form-control" id="codigo" />
                    <div v-if="form.errors.codigo" class="text-danger mt-1">{{ form.errors.codigo }}</div>
                  </div>

                  <div class="col-md-6">
                    <label for="latitude" class="form-label">Latitude</label>
                    <input v-model="form.latitude" type="text" class="form-control" id="latitude" />
                    <div v-if="form.errors.latitude" class="text-danger mt-1">{{ form.errors.latitude }}</div>
                  </div>

                  <div class="col-md-6">
                    <label for="longitude" class="form-label">Longitude</label>
                    <input v-model="form.longitude" type="text" class="form-control" id="longitude" />
                    <div v-if="form.errors.longitude" class="text-danger mt-1">{{ form.errors.longitude }}</div>
                  </div>

                  <div class="col-md-6">
                    <label for="cidade" class="form-label">Cidade</label>
                    <input v-model="form.cidade" type="text" class="form-control" id="cidade" />
                    <div v-if="form.errors.cidade" class="text-danger mt-1">{{ form.errors.cidade }}</div>
                  </div>

                  <div class="col-md-6">
                    <label for="estado" class="form-label">Estado</label>
                    <select v-model="form.estado" class="form-select" id="estado">
                      <option value="">Selecione</option>
                      <option v-for="uf in estados" :key="uf" :value="uf">{{ uf }}</option>
                    </select>
                    <div v-if="form.errors.estado" class="text-danger mt-1">{{ form.errors.estado }}</div>
                  </div>

                  <div class="col-md-6">
                    <label for="endereco" class="form-label">Endereço</label>
                    <input v-model="form.endereco" type="text" class="form-control" id="endereco" />
                    <div v-if="form.errors.endereco" class="text-danger mt-1">{{ form.errors.endereco }}</div>
                  </div>

                  <div class="col-md-3">
                    <label for="numero" class="form-label">Número</label>
                    <input v-model="form.numero" type="text" class="form-control" id="numero" />
                    <div v-if="form.errors.numero" class="text-danger mt-1">{{ form.errors.numero }}</div>
                  </div>

                  <div class="col-md-3">
                    <label for="cep" class="form-label">CEP</label>
                    <input v-model="form.cep" type="text" class="form-control" id="cep" />
                    <div v-if="form.errors.cep" class="text-danger mt-1">{{ form.errors.cep }}</div>
                  </div>

                </div>

                <div class="mt-4">
                  <button type="submit" class="btn btn-primary text-right" :disabled="form.processing">
                    Salvar
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

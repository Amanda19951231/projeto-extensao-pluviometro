<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import $ from 'jquery';
import 'datatables.net-bs5';
import 'datatables.net-buttons-bs5';
import jsZip from 'jszip';
import pdfMake from 'pdfmake/build/pdfmake';
import * as pdfFonts from 'pdfmake/build/vfs_fonts';
import 'datatables.net-buttons/js/buttons.html5';
import 'datatables.net-buttons/js/buttons.print';

pdfMake.vfs = pdfFonts.default;

window.JSZip = jsZip;

defineProps({
    pluviometros: Array
});

onMounted(() => {
    const table = $('#relatorios-table').DataTable({
        responsive: true,
        pageLength: 25,
        lengthMenu: [
            [10, 25, 50, 100, -1],
            [10, 25, 50, 100, 'Todos']
        ],
        lengthChange: true,
        dom:
            '<"row mb-3"' +
            '<"col-md-3 d-flex align-items-center"l>' +
            '<"col-md-6 text-center"B>' +
            '<"col-md-3 d-flex justify-content-end"f>' +
            '>' +
            'rt' +
            '<"row mt-3"' +
            '<"col-md-6"i>' +
            '<"col-md-6 p-0"p>' +
            '>',
        buttons: [
            {
                extend: 'copy',
                title: 'Relatório - Pluviômetros',
                exportOptions: {
                    columns: [0, 1],
                },
            },
            {
                extend: 'excel',
                title: 'Relatório - Pluviômetros',
                filename: 'Relatório - Pluviômetros',
                exportOptions: {
                    columns: ':not(.no-export)',
                },
            },
            {
                extend: 'pdf',
                title: 'Relatório - Pluviômetros',
                filename: 'Relatório - Pluviômetros',
                orientation: 'landscape',
                pageSize: 'A4',
                exportOptions: {
                    columns: ':not(.no-export)',
                },
                customize: function (doc) {
                    doc.content[1].table.headerRows = 1;
                },
            },
            {
                extend: 'print',
                title: 'Relatório - Pluviômetros',
                exportOptions: {
                    columns: ':not(.no-export)',
                },
            },
            // {
            //     extend: 'colvis',
            //     text: 'Colunas'
            // },
        ],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json',
            search: '',              // remove texto padrão
            searchPlaceholder: 'Pesquisar...',  // placeholder customizado
            lengthMenu: '_MENU_ registros por página'
        },
        initComplete: function () {
            // 1) estilizar o select de page-length
            const $len = $('div.dataTables_length select');
            $len.addClass('form-select form-select-sm');

            // 2) estilizar o input de busca
            const $searchInput = $('div.dataTables_filter input');
            $searchInput
                .addClass('form-control form-control-sm')
                .attr('placeholder', this.s.settings()[0].oLanguage.searchPlaceholder);

            // 3) opcional: envolver o botão de busca num input-group
            $('div.dataTables_filter').addClass('overflow-auto');
        }
    });
});
</script>

<template>

    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl mt-3 mb-3 font-semibold leading-tight text-gray-800 text-center">
                Pluviômetros
            </h2>
        </template>
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <table id="relatorios-table" class="table table-bordered table-striped table-hover w-100">
                <thead class="table-dark">
                    <tr>
                        <th>Código</th>
                        <th>Temperatura</th>
                        <th>Umidade</th>
                        <th>Chuva</th>
                        <th>Enviado último dado</th>
                        <th class="no-export text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in pluviometros" :key="item.id_pluviometro">
                        <td>{{ item.numero_serie }}</td>
                        <td>{{ item.temperatura }}</td>
                        <td>{{ item.umidade }}</td>
                        <td>{{ item.chuva }}</td>
                        <td>
                            {{
                                item.data_hora && new Date(item.data_hora).getFullYear() > 2000
                                    ? new Date(item.data_hora).toLocaleString('pt-BR', {
                                        day: '2-digit',
                                        month: '2-digit',
                                        year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                            })
                            : 'Ainda não recebido'
                            }}
                        </td>
                        <td class="text-center">
                            <a :href="`/pluviometros/${item.id_pluviometro}/edit`" class="text-primary me-2">
                                <i class="material-icons">&#xE254;</i>
                            </a>
                            <form :action="`/pluviometros/${item.id_pluviometro}`" method="POST" class="d-inline">
                                <input type="hidden" name="_method" value="DELETE" />
                                <input type="hidden" name="_token" :value="csrfToken" />
                                <button type="submit" class="btn btn-link text-danger p-0 m-0" style="cursor:pointer;">
                                    <i class="material-icons">&#xE872;</i>
                                </button>
                            </form>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
    </AuthenticatedLayout>
</template>


<style scoped>
/* Alinhamento de busca e paginação */
div.dataTables_wrapper div.dataTables_filter,
div.dataTables_wrapper div.dataTables_paginate {
    float: right !important;
    text-align: right !important;
}

@media print {
    .no-export {
        display: none !important;
    }
}
</style>
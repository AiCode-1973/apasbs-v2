#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Gera sql/opme_dados.sql — a carga inicial do OPME, extraída da planilha
"Opme 2026 Atual.xlsx".

A planilha tinha uma aba por mês e repetia a nota inteira em cada mês em que
caía uma parcela: 630 linhas para 265 notas. Aqui os dados já vêm agrupados
como o sistema guarda — nota fiscal com suas parcelas.

O conteúdo mora neste arquivo, em estrutura. Corrigir um valor é mudar uma
linha em Python e gerar o SQL de novo:

    python3 tools/gerar_opme_dados.py > sql/opme_dados.sql

Cada nota é um dicionário com:
    paciente, data_cirurgia, hospital, medico, fornecedor, numero_nf,
    valor_total, data_nf, total_parcelas, situacao, encaminhado,
    observacao, parcelas[{numero, vencimento, valor}]

Sobre a situação:
    normal     — a nota comum, que vai ser paga
    cancelada  — a planilha escrevia "NF cancelada" no lugar do vencimento
    doacao     — material doado, escrito "Doação" na linha

Notas canceladas e doações entram no sistema para o histórico ficar completo,
mas ficam fora das somas de vencimento.
"""

NOTAS = [
 {
  "paciente": "Viviana Teles de Oliveira",
  "data_cirurgia": "2025-08-28",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "3824",
  "valor_total": 17800.0,
  "data_nf": "2025-09-10",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 4,
    "vencimento": "2026-01-10",
    "valor": 4450.0
   }
  ]
 },
 {
  "paciente": "Luiz Carlos Alves Pereira",
  "data_cirurgia": "2025-08-22",
  "hospital": "HSE",
  "medico": "Luis Felipe",
  "fornecedor": "Leoni",
  "numero_nf": "17.172",
  "valor_total": 19000.0,
  "data_nf": "2025-09-15",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 3,
    "vencimento": "2026-01-10",
    "valor": 6334.0
   }
  ]
 },
 {
  "paciente": "Robenia Cavalcante Gonçalves Bassi de Lima",
  "data_cirurgia": "2025-08-30",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Litoral Medic",
  "numero_nf": "5385",
  "valor_total": 50560.0,
  "data_nf": "2025-09-09",
  "total_parcelas": 5,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 4,
    "vencimento": "2026-01-10",
    "valor": 10112.0
   },
   {
    "numero": 5,
    "vencimento": "2026-02-10",
    "valor": 10112.0
   }
  ]
 },
 {
  "paciente": "João Figueira de Faria Filho",
  "data_cirurgia": "2025-08-30",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "3820",
  "valor_total": 17800.0,
  "data_nf": "2025-09-09",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 4,
    "vencimento": "2026-01-10",
    "valor": 4450.0
   }
  ]
 },
 {
  "paciente": "Moacir Rodrigues de Souza",
  "data_cirurgia": "2025-09-01",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "3844",
  "valor_total": 17800.0,
  "data_nf": "2025-09-16",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 4,
    "vencimento": "2026-01-10",
    "valor": 4450.0
   }
  ]
 },
 {
  "paciente": "Telma Rodrigues Pupo da Silva",
  "data_cirurgia": "2025-08-29",
  "hospital": "HSE",
  "medico": "Monica Mazzurana",
  "fornecedor": "Mogami",
  "numero_nf": "18.832",
  "valor_total": 8039.35,
  "data_nf": "2025-09-12",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 4,
    "vencimento": "2026-01-17",
    "valor": 2009.83
   }
  ]
 },
 {
  "paciente": "Lenice Vitar Manzoti Benfato",
  "data_cirurgia": "2025-08-02",
  "hospital": "",
  "medico": "",
  "fornecedor": "Mogami",
  "numero_nf": "23.273",
  "valor_total": 1191.0,
  "data_nf": "2025-09-29",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 3,
    "vencimento": "2026-01-05",
    "valor": 497.0
   }
  ]
 },
 {
  "paciente": "Maria Lucia Simões de Oliveira",
  "data_cirurgia": "2025-09-16",
  "hospital": "HSE",
  "medico": "Breno Faria",
  "fornecedor": "SD Implant",
  "numero_nf": "5.567",
  "valor_total": 48900.0,
  "data_nf": "2025-09-24",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 3,
    "vencimento": "2026-01-10",
    "valor": 12225.0
   },
   {
    "numero": 3,
    "vencimento": "2026-02-10",
    "valor": 12225.0
   },
   {
    "numero": 4,
    "vencimento": "2026-03-10",
    "valor": 12225.0
   },
   {
    "numero": 4,
    "vencimento": "2026-04-10",
    "valor": 12225.0
   }
  ]
 },
 {
  "paciente": "Isaias Rodrigues Bezerra",
  "data_cirurgia": "2025-10-11",
  "hospital": "São Lucas Santos",
  "medico": "Rubens Palma",
  "fornecedor": "Plantão Médico",
  "numero_nf": "294.156",
  "valor_total": 4620.0,
  "data_nf": "2025-10-23",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 2,
    "vencimento": "2026-01-10",
    "valor": 2310.0
   }
  ]
 },
 {
  "paciente": "João Baptista de Freitas",
  "data_cirurgia": "2025-09-25",
  "hospital": "HSE",
  "medico": "Alissom Zamara",
  "fornecedor": "Medic Solution",
  "numero_nf": "263.999",
  "valor_total": 18595.0,
  "data_nf": "2025-10-06",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 3,
    "vencimento": "2026-01-10",
    "valor": 6198.46
   }
  ]
 },
 {
  "paciente": "Maria de Fátima Pereira Nicolucci",
  "data_cirurgia": "2025-08-26",
  "hospital": "HSE",
  "medico": "Carlo Alba",
  "fornecedor": "Itamedical",
  "numero_nf": "8.768",
  "valor_total": 12000.0,
  "data_nf": "2025-09-12",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 3,
    "vencimento": "2026-01-10",
    "valor": 4000.0
   }
  ]
 },
 {
  "paciente": "Carlos Alberto Harwalys",
  "data_cirurgia": "2025-10-01",
  "hospital": "HSE",
  "medico": "Felipe Guilherme",
  "fornecedor": "Phisiomed",
  "numero_nf": "326.944",
  "valor_total": 11300.0,
  "data_nf": "2025-10-10",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 2,
    "vencimento": "2026-01-10",
    "valor": 2825.0
   },
   {
    "numero": 3,
    "vencimento": "2026-02-10",
    "valor": 2825.0
   },
   {
    "numero": 4,
    "vencimento": "2026-03-10",
    "valor": 2825.0
   },
   {
    "numero": 4,
    "vencimento": "2026-04-10",
    "valor": 2825.0
   }
  ]
 },
 {
  "paciente": "Carlos Alberto Harwalys",
  "data_cirurgia": "2025-10-01",
  "hospital": "HSE",
  "medico": "",
  "fornecedor": "Mogami",
  "numero_nf": "27.265",
  "valor_total": 504.0,
  "data_nf": "2025-10-13",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 3,
    "vencimento": "2026-01-19",
    "valor": 168.0
   }
  ]
 },
 {
  "paciente": "Roseli dos Anjos Pereira Gonçalves",
  "data_cirurgia": "2025-09-27",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "3985",
  "valor_total": 17800.0,
  "data_nf": "2025-10-09",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 3,
    "vencimento": "2026-01-10",
    "valor": 4450.0
   },
   {
    "numero": 4,
    "vencimento": "2026-02-10",
    "valor": 4450.0
   }
  ]
 },
 {
  "paciente": "Maria Helena Mendes",
  "data_cirurgia": "2025-10-06",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "3997",
  "valor_total": 17800.0,
  "data_nf": "2025-10-10",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 3,
    "vencimento": "2026-01-10",
    "valor": 4450.0
   },
   {
    "numero": 4,
    "vencimento": "2026-02-10",
    "valor": 4450.0
   }
  ]
 },
 {
  "paciente": "Oscar Americo de Veras",
  "data_cirurgia": "2025-09-26",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "3989",
  "valor_total": 17800.0,
  "data_nf": "2025-10-09",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 3,
    "vencimento": "2026-01-10",
    "valor": 4450.0
   },
   {
    "numero": 4,
    "vencimento": "2026-02-10",
    "valor": 4450.0
   }
  ]
 },
 {
  "paciente": "Claudiomar Queiroz",
  "data_cirurgia": "2025-10-02",
  "hospital": "HSE",
  "medico": "Andre Luis D. Costa",
  "fornecedor": "Millenium",
  "numero_nf": "3995",
  "valor_total": 76080.02,
  "data_nf": "2025-10-09",
  "total_parcelas": 6,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 3,
    "vencimento": "2026-01-10",
    "valor": 12680.0
   },
   {
    "numero": 4,
    "vencimento": "2026-02-10",
    "valor": 12680.0
   },
   {
    "numero": 5,
    "vencimento": "2026-03-10",
    "valor": 12680.0
   },
   {
    "numero": 6,
    "vencimento": "2026-04-10",
    "valor": 12680.0
   }
  ]
 },
 {
  "paciente": "Adriana de Araujo Rittes",
  "data_cirurgia": "2025-09-29",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "3986",
  "valor_total": 12000.0,
  "data_nf": "2025-10-09",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 3,
    "vencimento": "2026-01-10",
    "valor": 4000.0
   }
  ]
 },
 {
  "paciente": "Sthefanie de Morais",
  "data_cirurgia": "2025-09-18",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "3991",
  "valor_total": 17800.0,
  "data_nf": "2025-10-09",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 3,
    "vencimento": "2026-01-10",
    "valor": 5933.43
   }
  ]
 },
 {
  "paciente": "Maria de Lourdes dos Santos Ramos",
  "data_cirurgia": "2025-09-27",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "3990",
  "valor_total": 35000.0,
  "data_nf": "2025-10-09",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 3,
    "vencimento": "2026-01-10",
    "valor": 8750.0
   },
   {
    "numero": 4,
    "vencimento": "2026-02-10",
    "valor": 8750.0
   }
  ]
 },
 {
  "paciente": "Cleonice Ribeiro de Oliveira",
  "data_cirurgia": "2025-09-23",
  "hospital": "HSE",
  "medico": "Breno Faria",
  "fornecedor": "Leoni",
  "numero_nf": "17.265",
  "valor_total": 10100.0,
  "data_nf": "2025-10-07",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 2,
    "vencimento": "2026-01-10",
    "valor": 5050.0
   }
  ]
 },
 {
  "paciente": "Irene Gomes dos Santos",
  "data_cirurgia": "2025-09-24",
  "hospital": "HSE",
  "medico": "Luis Felipe",
  "fornecedor": "Surgipro",
  "numero_nf": "1",
  "valor_total": 8040.0,
  "data_nf": "2025-10-01",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 3,
    "vencimento": "2026-01-10",
    "valor": 2680.0
   }
  ]
 },
 {
  "paciente": "Cleyde de Albuquerque Carletti",
  "data_cirurgia": "2025-10-15",
  "hospital": "HSE",
  "medico": "Jefferson dos Santos",
  "fornecedor": "Biotronk",
  "numero_nf": "1151669",
  "valor_total": 8400.0,
  "data_nf": "2025-10-20",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 2,
    "vencimento": "2026-01-10",
    "valor": 2799.97
   },
   {
    "numero": 3,
    "vencimento": "2026-02-10",
    "valor": 2800.06
   }
  ]
 },
 {
  "paciente": "Roberto Aureo Vila Boas",
  "data_cirurgia": "2025-09-17",
  "hospital": "HSE",
  "medico": "Jose Luis Santos",
  "fornecedor": "Bio2 New Possibilities",
  "numero_nf": "86406",
  "valor_total": 9780.0,
  "data_nf": "2025-10-07",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 3,
    "vencimento": "2026-01-10",
    "valor": 3290.0
   }
  ]
 },
 {
  "paciente": "Anameure de Carvalho Seixas Santos",
  "data_cirurgia": "2025-10-10",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "4026",
  "valor_total": 16950.0,
  "data_nf": "2025-10-17",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 3,
    "vencimento": "2026-01-10",
    "valor": 4237.5
   },
   {
    "numero": 4,
    "vencimento": "2026-02-10",
    "valor": 4237.5
   }
  ]
 },
 {
  "paciente": "Vilma Menezes da Silva",
  "data_cirurgia": "2025-10-14",
  "hospital": "HSE",
  "medico": "Andre Luis D. Costa",
  "fornecedor": "Millenium",
  "numero_nf": "4039",
  "valor_total": 18000.02,
  "data_nf": "2025-10-20",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 3,
    "vencimento": "2026-01-10",
    "valor": 4499.99
   },
   {
    "numero": 4,
    "vencimento": "2026-02-10",
    "valor": 4499.99
   }
  ]
 },
 {
  "paciente": "Reinaldo Aurelio Pereira",
  "data_cirurgia": "2026-09-26",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "4040",
  "valor_total": 17800.0,
  "data_nf": "2025-10-20",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 3,
    "vencimento": "2026-01-10",
    "valor": 4450.0
   },
   {
    "numero": 4,
    "vencimento": "2026-02-10",
    "valor": 4450.0
   }
  ]
 },
 {
  "paciente": "Jose Colombo Rodio",
  "data_cirurgia": "2025-10-13",
  "hospital": "",
  "medico": "Erica de Gerard",
  "fornecedor": "Leoni",
  "numero_nf": "17.352",
  "valor_total": 1000.0,
  "data_nf": "2025-10-21",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-01-10",
    "valor": 1000.0
   }
  ]
 },
 {
  "paciente": "Maria Rosangela da Silva de Melo",
  "data_cirurgia": "2025-07-14",
  "hospital": "HSE",
  "medico": "Carlo Alba",
  "fornecedor": "Itamedical",
  "numero_nf": "8953",
  "valor_total": 12000.0,
  "data_nf": "2025-10-22",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 2,
    "vencimento": "2026-01-10",
    "valor": 3000.0
   },
   {
    "numero": 3,
    "vencimento": "2026-02-10",
    "valor": 3000.0
   },
   {
    "numero": 4,
    "vencimento": "2026-03-10",
    "valor": 3000.0
   }
  ]
 },
 {
  "paciente": "Jose Carlos Barriento",
  "data_cirurgia": "2025-10-13",
  "hospital": "HSE",
  "medico": "Carlo Alba",
  "fornecedor": "Itamedical",
  "numero_nf": "8954",
  "valor_total": 12000.0,
  "data_nf": "2025-10-22",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 2,
    "vencimento": "2026-01-10",
    "valor": 3000.0
   },
   {
    "numero": 3,
    "vencimento": "2026-02-10",
    "valor": 3000.0
   },
   {
    "numero": 4,
    "vencimento": "2026-03-10",
    "valor": 3000.0
   }
  ]
 },
 {
  "paciente": "Isaias Rodrigues Bezerra",
  "data_cirurgia": "2025-10-11",
  "hospital": "",
  "medico": "Rubens Palma",
  "fornecedor": "Medic Solution",
  "numero_nf": "265.876",
  "valor_total": 31445.0,
  "data_nf": "2025-10-23",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 3,
    "vencimento": "2026-01-21",
    "valor": 10481.88
   }
  ]
 },
 {
  "paciente": "Julio Cesar de Carlos",
  "data_cirurgia": "2025-10-04",
  "hospital": "HSE",
  "medico": "Itallo Braga",
  "fornecedor": "Russer",
  "numero_nf": "103.973",
  "valor_total": 2204.0,
  "data_nf": "2025-10-21",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-01-19",
    "valor": 2204.0
   }
  ]
 },
 {
  "paciente": "Cintia Aparecida de Macedo da Silva",
  "data_cirurgia": "2025-10-28",
  "hospital": "HSE",
  "medico": "Daniel Paganini",
  "fornecedor": "Itamedical",
  "numero_nf": "9013",
  "valor_total": 1500.0,
  "data_nf": "2025-10-29",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-01-10",
    "valor": 750.0
   },
   {
    "numero": 2,
    "vencimento": "2026-02-10",
    "valor": 750.0
   }
  ]
 },
 {
  "paciente": "Isaias Salviano",
  "data_cirurgia": "2025-10-09",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "4066",
  "valor_total": 16950.0,
  "data_nf": "2025-10-23",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 2,
    "vencimento": "2026-01-10",
    "valor": 4237.5
   },
   {
    "numero": 3,
    "vencimento": "2026-02-10",
    "valor": 4237.5
   },
   {
    "numero": 4,
    "vencimento": "2026-03-10",
    "valor": 4237.5
   }
  ]
 },
 {
  "paciente": "Marina Severina Alves Vairo",
  "data_cirurgia": "2025-10-16",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Litoral Medic",
  "numero_nf": "5514",
  "valor_total": 2000.0,
  "data_nf": "2025-10-23",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-01-10",
    "valor": 2000.0
   }
  ]
 },
 {
  "paciente": "Maria Eunice Lime Panhome",
  "data_cirurgia": "2025-10-15",
  "hospital": "HSE",
  "medico": "Alexandre Augusto",
  "fornecedor": "Surgipro",
  "numero_nf": "4",
  "valor_total": 8040.0,
  "data_nf": "2025-10-27",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 2,
    "vencimento": "2026-01-10",
    "valor": 4020.0
   }
  ]
 },
 {
  "paciente": "Ian Filipe Barbosa Ramos",
  "data_cirurgia": "2025-10-31",
  "hospital": "HSE",
  "medico": "",
  "fornecedor": "Russer",
  "numero_nf": "104.429",
  "valor_total": 215.0,
  "data_nf": "2025-10-31",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-01-12",
    "valor": 215.0
   }
  ]
 },
 {
  "paciente": "Luiz Alves",
  "data_cirurgia": "2025-09-13",
  "hospital": "São Lucas Santos",
  "medico": "Alissom Zamara",
  "fornecedor": "Plantão Médico",
  "numero_nf": "294.617",
  "valor_total": 23580.0,
  "data_nf": "2025-10-29",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 2,
    "vencimento": "2026-01-10",
    "valor": 7860.0
   },
   {
    "numero": 3,
    "vencimento": "2026-02-10",
    "valor": 7860.0
   }
  ]
 },
 {
  "paciente": "Giovanni Guedes Ribeiro",
  "data_cirurgia": "2025-10-29",
  "hospital": "HSE",
  "medico": "Carlo Alba",
  "fornecedor": "Itamedical",
  "numero_nf": "9.033",
  "valor_total": 5700.0,
  "data_nf": "2025-11-04",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-01-10",
    "valor": 2850.0
   },
   {
    "numero": 2,
    "vencimento": "2026-02-10",
    "valor": 2850.0
   }
  ]
 },
 {
  "paciente": "Marcos Antonio de Oliveira",
  "data_cirurgia": "2025-09-17",
  "hospital": "HSE",
  "medico": "Tarcisio Abib",
  "fornecedor": "Russer",
  "numero_nf": "104.558",
  "valor_total": 7990.0,
  "data_nf": "2025-11-04",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-01-12",
    "valor": 7990.0
   }
  ]
 },
 {
  "paciente": "Gabriel Oliveira Felix da Silva",
  "data_cirurgia": "2025-09-27",
  "hospital": "HSE",
  "medico": "Itallo Braga",
  "fornecedor": "Russer",
  "numero_nf": "104.431",
  "valor_total": 1415.0,
  "data_nf": "2025-10-31",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 2,
    "vencimento": "2026-01-12",
    "valor": 707.5
   },
   {
    "numero": 2,
    "vencimento": "2026-02-10",
    "valor": 707.5
   }
  ]
 },
 {
  "paciente": "Isaias Rodrigues Bezerra",
  "data_cirurgia": "2025-10-11",
  "hospital": "São Lucas Santos",
  "medico": "Rubens Palma",
  "fornecedor": "Invasive",
  "numero_nf": "147228",
  "valor_total": 8900.0,
  "data_nf": "2025-11-24",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 2,
    "vencimento": "2026-01-10",
    "valor": 2967.0
   },
   {
    "numero": 3,
    "vencimento": "2026-02-10",
    "valor": 2967.0
   }
  ]
 },
 {
  "paciente": "Gilberto Pereira de Souza",
  "data_cirurgia": "2025-10-16",
  "hospital": "",
  "medico": "Alissom Zamara",
  "fornecedor": "Medic Solution",
  "numero_nf": "266.710",
  "valor_total": 7595.0,
  "data_nf": "2025-10-31",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 2,
    "vencimento": "2026-01-10",
    "valor": 2531.64
   },
   {
    "numero": 3,
    "vencimento": "2026-02-10",
    "valor": 2531.72
   }
  ]
 },
 {
  "paciente": "Aires de Carvalho de Oliveira",
  "data_cirurgia": "2025-11-06",
  "hospital": "HSE",
  "medico": "Carlo Alba",
  "fornecedor": "Itamedical",
  "numero_nf": "9.104",
  "valor_total": 12000.0,
  "data_nf": "2025-11-14",
  "total_parcelas": 5,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "NF 91.05 R$500,00 doação",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-01-10",
    "valor": 2400.0
   },
   {
    "numero": 2,
    "vencimento": "2026-02-10",
    "valor": 2400.0
   },
   {
    "numero": 3,
    "vencimento": "2026-03-10",
    "valor": 2400.0
   },
   {
    "numero": 4,
    "vencimento": "2026-04-10",
    "valor": 2400.0
   },
   {
    "numero": 5,
    "vencimento": "2026-05-10",
    "valor": 2400.0
   }
  ]
 },
 {
  "paciente": "Antonio Jose Almeida da Silva",
  "data_cirurgia": "2025-11-10",
  "hospital": "HSE",
  "medico": "Luis Felipe",
  "fornecedor": "Millenium",
  "numero_nf": "4175",
  "valor_total": 1700.0,
  "data_nf": "2025-11-12",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-01-10",
    "valor": 1700.0
   }
  ]
 },
 {
  "paciente": "Alexander Alves dos Santos",
  "data_cirurgia": "2025-11-06",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "4188",
  "valor_total": 29770.02,
  "data_nf": "2025-11-13",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-01-10",
    "valor": 7442.51
   },
   {
    "numero": 2,
    "vencimento": "2026-02-10",
    "valor": 7442.51
   },
   {
    "numero": 3,
    "vencimento": "2026-03-10",
    "valor": 7442.51
   },
   {
    "numero": 4,
    "vencimento": "2026-04-10",
    "valor": 7442.51
   }
  ]
 },
 {
  "paciente": "Thais Nogueira Raymundo",
  "data_cirurgia": "2025-09-15",
  "hospital": "HSE",
  "medico": "",
  "fornecedor": "Russer",
  "numero_nf": "104.958",
  "valor_total": 1465.0,
  "data_nf": "2025-11-13",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-01-12",
    "valor": 1465.0
   }
  ]
 },
 {
  "paciente": "Cibele Cassilhas Mota Souza",
  "data_cirurgia": "2025-11-03",
  "hospital": "HSE",
  "medico": "Carlo Alba",
  "fornecedor": "Itamedical",
  "numero_nf": "9.088",
  "valor_total": 4500.0,
  "data_nf": "2025-11-11",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-01-10",
    "valor": 1500.0
   },
   {
    "numero": 2,
    "vencimento": "2026-02-10",
    "valor": 1500.0
   },
   {
    "numero": 3,
    "vencimento": "2026-03-10",
    "valor": 1500.0
   }
  ]
 },
 {
  "paciente": "Terezinha Silva Prieto",
  "data_cirurgia": "2025-09-27",
  "hospital": "HSE",
  "medico": "Itallo Braga",
  "fornecedor": "Russer",
  "numero_nf": "104.869",
  "valor_total": 2205.0,
  "data_nf": "2025-11-11",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-01-12",
    "valor": 1102.5
   },
   {
    "numero": 2,
    "vencimento": "2026-02-12",
    "valor": 1102.5
   }
  ]
 },
 {
  "paciente": "Alexandre Alves dos Santos",
  "data_cirurgia": "2025-11-06",
  "hospital": "HSE",
  "medico": "",
  "fornecedor": "Vaz. Schiavão",
  "numero_nf": "8395",
  "valor_total": 6000.0,
  "data_nf": "2025-11-12",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "Desconto para quitação.",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-01-30",
    "valor": 5631.0
   }
  ]
 },
 {
  "paciente": "Erick Henrique Fuchs Rosário",
  "data_cirurgia": "2025-11-08",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Litoral Medic",
  "numero_nf": "5573",
  "valor_total": 2000.0,
  "data_nf": "2025-11-12",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-01-10",
    "valor": 1000.0
   },
   {
    "numero": 2,
    "vencimento": "2026-02-10",
    "valor": 1000.0
   }
  ]
 },
 {
  "paciente": "Lenice Vitar Manzoti Benfato",
  "data_cirurgia": "2025-10-21",
  "hospital": "HSE",
  "medico": "",
  "fornecedor": "Saedmed",
  "numero_nf": "73.726",
  "valor_total": 952.0,
  "data_nf": "2025-11-13",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "Desconto para quitação.",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-01-12",
    "valor": 952.0
   }
  ]
 },
 {
  "paciente": "Marli de Lima",
  "data_cirurgia": "2025-11-08",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Litoral Medic",
  "numero_nf": "5578",
  "valor_total": 2000.0,
  "data_nf": "2025-11-13",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-01-10",
    "valor": 1000.0
   },
   {
    "numero": 2,
    "vencimento": "2026-02-10",
    "valor": 1000.0
   }
  ]
 },
 {
  "paciente": "Alessandra Mendonça de Souza Alves",
  "data_cirurgia": "2025-11-13",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "4207",
  "valor_total": 1998.0,
  "data_nf": "2025-11-18",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-01-10",
    "valor": 1998.0
   }
  ]
 },
 {
  "paciente": "Antonio Carlos Zacchinni",
  "data_cirurgia": "2025-11-04",
  "hospital": "HSE",
  "medico": "Breno Faria",
  "fornecedor": "SD Implant",
  "numero_nf": "5682",
  "valor_total": 48000.0,
  "data_nf": "2025-11-10",
  "total_parcelas": 5,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-01-10",
    "valor": 9600.0
   },
   {
    "numero": 2,
    "vencimento": "2026-02-10",
    "valor": 9600.0
   },
   {
    "numero": 3,
    "vencimento": "2026-03-10",
    "valor": 9600.0
   },
   {
    "numero": 4,
    "vencimento": "2026-04-10",
    "valor": 9600.0
   },
   {
    "numero": 5,
    "vencimento": "2026-05-10",
    "valor": 9600.0
   }
  ]
 },
 {
  "paciente": "Mariana Santos Nogueira",
  "data_cirurgia": "2025-10-22",
  "hospital": "HSE",
  "medico": "Carlos Renato",
  "fornecedor": "Custom Medical",
  "numero_nf": "7488",
  "valor_total": 6800.0,
  "data_nf": "2025-11-17",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-01-10",
    "valor": 3400.0
   },
   {
    "numero": 2,
    "vencimento": "2026-02-10",
    "valor": 3400.0
   }
  ]
 },
 {
  "paciente": "Antonia Marques dos Santos",
  "data_cirurgia": "2025-08-28",
  "hospital": "HSE",
  "medico": "",
  "fornecedor": "Isomedical",
  "numero_nf": "88.225",
  "valor_total": 1665.0,
  "data_nf": "2025-11-18",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-01-10",
    "valor": 1665.0
   }
  ]
 },
 {
  "paciente": "Valter Saladino Santi",
  "data_cirurgia": "2025-09-19",
  "hospital": "HSE",
  "medico": "",
  "fornecedor": "Saedmed",
  "numero_nf": "73.919",
  "valor_total": 1344.0,
  "data_nf": "2025-11-24",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-01-23",
    "valor": 1344.0
   }
  ]
 },
 {
  "paciente": "Maria Ferreira de Souza",
  "data_cirurgia": "2025-11-19",
  "hospital": "HSE",
  "medico": "Luis Felipe",
  "fornecedor": "Surgipro",
  "numero_nf": "13",
  "valor_total": 6290.0,
  "data_nf": "2025-11-27",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-01-10",
    "valor": 2096.68
   },
   {
    "numero": 2,
    "vencimento": "2026-02-10",
    "valor": 2096.68
   },
   {
    "numero": 3,
    "vencimento": "2026-03-10",
    "valor": 2096.68
   }
  ]
 },
 {
  "paciente": "Maria Izabel Francisca Correia",
  "data_cirurgia": "2025-08-21",
  "hospital": "",
  "medico": "",
  "fornecedor": "E.tamussino",
  "numero_nf": "267164",
  "valor_total": 2250.0,
  "data_nf": "2025-11-18",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-01-12",
    "valor": 1275.0
   },
   {
    "numero": 2,
    "vencimento": "2026-02-12",
    "valor": 1275.0
   }
  ]
 },
 {
  "paciente": "Celia Maria da Silva Menezes",
  "data_cirurgia": "2025-09-04",
  "hospital": "",
  "medico": "",
  "fornecedor": "Saedmed",
  "numero_nf": "74.077",
  "valor_total": 1344.0,
  "data_nf": "2025-12-01",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-01-30",
    "valor": 1344.0
   }
  ]
 },
 {
  "paciente": "Debora Carneiro de Carvalho",
  "data_cirurgia": "2025-11-28",
  "hospital": "HSE",
  "medico": "Erica de Gerard",
  "fornecedor": "Biomedicall",
  "numero_nf": "1.070",
  "valor_total": 800.0,
  "data_nf": "2025-12-02",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-01-02",
    "valor": 800.0
   }
  ]
 },
 {
  "paciente": "Antonio Mores Costa",
  "data_cirurgia": "2025-10-13",
  "hospital": "HSE",
  "medico": "",
  "fornecedor": "Saedmed",
  "numero_nf": "74.100",
  "valor_total": 1344.0,
  "data_nf": "2025-12-02",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-01-31",
    "valor": 1344.0
   }
  ]
 },
 {
  "paciente": "Elida Soares de Oliveira Furtada",
  "data_cirurgia": "2025-11-06",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Litoral Medic",
  "numero_nf": "5574",
  "valor_total": 1000.0,
  "data_nf": "2025-11-12",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-01-10",
    "valor": 500.0
   },
   {
    "numero": 2,
    "vencimento": "2026-02-10",
    "valor": 500.0
   }
  ]
 },
 {
  "paciente": "Luiz Alves",
  "data_cirurgia": "2025-09-13",
  "hospital": "São Lucas Santos",
  "medico": "Alissom Zamara",
  "fornecedor": "Enva",
  "numero_nf": "6.478",
  "valor_total": 10000.0,
  "data_nf": "2025-12-03",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-02-10",
    "valor": 2500.0
   },
   {
    "numero": 2,
    "vencimento": "2026-03-10",
    "valor": 2500.0
   },
   {
    "numero": 3,
    "vencimento": "2026-04-10",
    "valor": 2500.0
   },
   {
    "numero": 4,
    "vencimento": "2026-05-10",
    "valor": 2500.0
   }
  ]
 },
 {
  "paciente": "Carlo Alberto Carvalho Ferreira",
  "data_cirurgia": "2025-11-12",
  "hospital": "HSE",
  "medico": "Alexandre",
  "fornecedor": "Leoni",
  "numero_nf": "17.524",
  "valor_total": 800.0,
  "data_nf": "2025-12-01",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-02-10",
    "valor": 800.0
   }
  ]
 },
 {
  "paciente": "Sonia Nascimento da Mota",
  "data_cirurgia": "2025-11-18",
  "hospital": "HSE",
  "medico": "Breno Faria",
  "fornecedor": "SD Implant",
  "numero_nf": "5.734",
  "valor_total": 49280.0,
  "data_nf": "2025-11-28",
  "total_parcelas": 5,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-02-10",
    "valor": 9856.0
   },
   {
    "numero": 2,
    "vencimento": "2026-03-10",
    "valor": 9856.0
   },
   {
    "numero": 3,
    "vencimento": "2026-04-10",
    "valor": 9856.0
   },
   {
    "numero": 4,
    "vencimento": "2026-05-10",
    "valor": 9856.0
   },
   {
    "numero": 5,
    "vencimento": "2026-06-10",
    "valor": 9856.0
   }
  ]
 },
 {
  "paciente": "Pedro Henrique Manzo",
  "data_cirurgia": "2025-11-21",
  "hospital": "HSE",
  "medico": "",
  "fornecedor": "Russer",
  "numero_nf": "105.710",
  "valor_total": 2205.0,
  "data_nf": "2025-12-02",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-02-10",
    "valor": 1102.5
   },
   {
    "numero": 2,
    "vencimento": "2026-03-10",
    "valor": 1102.5
   }
  ]
 },
 {
  "paciente": "Maria Lenice Bernardes Branco",
  "data_cirurgia": "2025-11-25",
  "hospital": "HSE",
  "medico": "Erica Gerard",
  "fornecedor": "Russer",
  "numero_nf": "4287",
  "valor_total": 980.0,
  "data_nf": "2025-12-04",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-02-10",
    "valor": 980.0
   }
  ]
 },
 {
  "paciente": "Luciana Cardoso dos Nascimento",
  "data_cirurgia": "2025-10-21",
  "hospital": "",
  "medico": "",
  "fornecedor": "Isomedical",
  "numero_nf": "89.497",
  "valor_total": 3230.0,
  "data_nf": "2025-12-09",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-02-10",
    "valor": 3230.0
   }
  ]
 },
 {
  "paciente": "Giovana Rabotzke Saramento",
  "data_cirurgia": "2025-09-18",
  "hospital": "",
  "medico": "",
  "fornecedor": "Isomedical",
  "numero_nf": "89.496",
  "valor_total": 3230.0,
  "data_nf": "2025-12-09",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-02-10",
    "valor": 3230.0
   }
  ]
 },
 {
  "paciente": "Gabriel de Almeida Leite",
  "data_cirurgia": "2025-11-19",
  "hospital": "HSE",
  "medico": "Luis Felipe",
  "fornecedor": "Leoni",
  "numero_nf": "17.503",
  "valor_total": 17000.0,
  "data_nf": "2025-11-26",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-02-10",
    "valor": 5667.8
   },
   {
    "numero": 2,
    "vencimento": "2026-03-10",
    "valor": 5667.8
   },
   {
    "numero": 3,
    "vencimento": "2026-04-10",
    "valor": 5667.8
   }
  ]
 },
 {
  "paciente": "Nilton Monte",
  "data_cirurgia": "2025-09-24",
  "hospital": "",
  "medico": "",
  "fornecedor": "Russer",
  "numero_nf": "105.494",
  "valor_total": 1480.0,
  "data_nf": "2025-11-27",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-02-10",
    "valor": 1480.0
   }
  ]
 },
 {
  "paciente": "Edson Antonio dos Santos",
  "data_cirurgia": "2025-11-18",
  "hospital": "HSE",
  "medico": "Breno Faria",
  "fornecedor": "Leoni",
  "numero_nf": "17.484",
  "valor_total": 9400.0,
  "data_nf": "2025-11-19",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-02-10",
    "valor": 4700.0
   },
   {
    "numero": 2,
    "vencimento": "2026-03-10",
    "valor": 4700.0
   }
  ]
 },
 {
  "paciente": "Alexandre Magno Alcantara Costa",
  "data_cirurgia": "2025-11-15",
  "hospital": "HSE",
  "medico": "Luis Felipe",
  "fornecedor": "Leoni",
  "numero_nf": "17.497",
  "valor_total": 3800.0,
  "data_nf": "2025-11-24",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-02-10",
    "valor": 3800.0
   }
  ]
 },
 {
  "paciente": "Ivaneia Quintanilha Mariano de Souza",
  "data_cirurgia": "2025-11-01",
  "hospital": "",
  "medico": "Alessandra de Moura",
  "fornecedor": "Lan Medical",
  "numero_nf": "26.203",
  "valor_total": 71730.0,
  "data_nf": "2025-11-25",
  "total_parcelas": 5,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-02-10",
    "valor": 14346.0
   },
   {
    "numero": 2,
    "vencimento": "2026-03-10",
    "valor": 14346.0
   },
   {
    "numero": 3,
    "vencimento": "2026-04-07",
    "valor": 14346.0
   },
   {
    "numero": 4,
    "vencimento": "2026-05-05",
    "valor": 14346.0
   },
   {
    "numero": 5,
    "vencimento": "2026-06-02",
    "valor": 14346.0
   }
  ]
 },
 {
  "paciente": "Nathalia Cristina Gomes Prado",
  "data_cirurgia": "2026-11-28",
  "hospital": "HSE",
  "medico": "Anderson Galdo",
  "fornecedor": "Millenium",
  "numero_nf": "4286",
  "valor_total": 16950.0,
  "data_nf": "2025-12-04",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-02-10",
    "valor": 4237.5
   },
   {
    "numero": 2,
    "vencimento": "2026-03-10",
    "valor": 4237.5
   },
   {
    "numero": 3,
    "vencimento": "2026-04-10",
    "valor": 4237.5
   },
   {
    "numero": 4,
    "vencimento": "2026-05-10",
    "valor": 4237.5
   }
  ]
 },
 {
  "paciente": "Valter dos Passos Jr.",
  "data_cirurgia": "2025-11-27",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "4320",
  "valor_total": 16950.0,
  "data_nf": "2025-12-12",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-02-10",
    "valor": 4237.5
   },
   {
    "numero": 1,
    "vencimento": "2026-03-10",
    "valor": 4237.5
   },
   {
    "numero": 3,
    "vencimento": "2026-04-10",
    "valor": 4237.5
   },
   {
    "numero": 4,
    "vencimento": "2026-05-10",
    "valor": 4237.5
   }
  ]
 },
 {
  "paciente": "Manoel Edmilton da Silva",
  "data_cirurgia": "2025-11-27",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Litoral Medic",
  "numero_nf": "5686",
  "valor_total": 2400.0,
  "data_nf": "2025-12-11",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-02-10",
    "valor": 800.0
   },
   {
    "numero": 2,
    "vencimento": "2026-03-10",
    "valor": 800.0
   },
   {
    "numero": 3,
    "vencimento": "2026-04-10",
    "valor": 800.0
   }
  ]
 },
 {
  "paciente": "Rhaisa Martins Franco",
  "data_cirurgia": "2025-11-27",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Litoral Medic",
  "numero_nf": "5685",
  "valor_total": 2000.0,
  "data_nf": "2025-12-11",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-02-10",
    "valor": 666.67
   },
   {
    "numero": 2,
    "vencimento": "2026-03-10",
    "valor": 666.67
   },
   {
    "numero": 3,
    "vencimento": "2026-04-10",
    "valor": 666.67
   }
  ]
 },
 {
  "paciente": "Arleide Maria Simões dos Santos",
  "data_cirurgia": "2025-12-04",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Litoral Medic",
  "numero_nf": "5688",
  "valor_total": 2000.0,
  "data_nf": "2025-12-11",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-02-10",
    "valor": 1000.0
   },
   {
    "numero": 2,
    "vencimento": "2026-03-10",
    "valor": 1000.0
   }
  ]
 },
 {
  "paciente": "Helena Aparecida Ghiraldelli",
  "data_cirurgia": "2025-12-01",
  "hospital": "HSE",
  "medico": "Luis Felipe",
  "fornecedor": "Millenium",
  "numero_nf": "4350",
  "valor_total": 1700.0,
  "data_nf": "2025-12-16",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-02-10",
    "valor": 1700.0
   }
  ]
 },
 {
  "paciente": "Marcelo da Silva",
  "data_cirurgia": "2025-12-05",
  "hospital": "",
  "medico": "",
  "fornecedor": "Russer",
  "numero_nf": "106.346",
  "valor_total": 2235.0,
  "data_nf": "2025-12-16",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-02-10",
    "valor": 1117.5
   },
   {
    "numero": 2,
    "vencimento": "2026-03-10",
    "valor": 1117.5
   }
  ]
 },
 {
  "paciente": "Edgard Valentim Jr",
  "data_cirurgia": "2025-11-25",
  "hospital": "",
  "medico": "",
  "fornecedor": "Promeho",
  "numero_nf": "1.365",
  "valor_total": 1450.0,
  "data_nf": "2025-12-29",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-02-10",
    "valor": 725.0
   },
   {
    "numero": 2,
    "vencimento": "2026-03-10",
    "valor": 725.0
   }
  ]
 },
 {
  "paciente": "João Camilo Filho",
  "data_cirurgia": "2025-12-10",
  "hospital": "",
  "medico": "Sergio Slearuc",
  "fornecedor": "Storm",
  "numero_nf": "301.774",
  "valor_total": 2300.0,
  "data_nf": "2025-12-16",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-02-10",
    "valor": 2300.0
   },
   {
    "numero": 2,
    "vencimento": "2026-03-10",
    "valor": 1150.0
   }
  ]
 },
 {
  "paciente": "Cleide Aparecida Ramos Rosa",
  "data_cirurgia": "2025-12-01",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "4351",
  "valor_total": 16950.0,
  "data_nf": "2025-12-16",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-02-10",
    "valor": 4237.5
   },
   {
    "numero": 2,
    "vencimento": "2026-03-10",
    "valor": 4237.5
   },
   {
    "numero": 3,
    "vencimento": "2026-04-10",
    "valor": 4237.5
   },
   {
    "numero": 4,
    "vencimento": "2026-05-10",
    "valor": 4237.5
   }
  ]
 },
 {
  "paciente": "Neiri Souza de Farias",
  "data_cirurgia": "2025-12-02",
  "hospital": "HSE",
  "medico": "Breno Faria",
  "fornecedor": "SD Implant",
  "numero_nf": "5769",
  "valor_total": 48000.0,
  "data_nf": "2025-12-05",
  "total_parcelas": 5,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-02-10",
    "valor": 9600.0
   },
   {
    "numero": 2,
    "vencimento": "2026-03-10",
    "valor": 9600.0
   },
   {
    "numero": 3,
    "vencimento": "2026-04-10",
    "valor": 9600.0
   },
   {
    "numero": 4,
    "vencimento": "2026-05-10",
    "valor": 9600.0
   },
   {
    "numero": 5,
    "vencimento": "2026-06-10",
    "valor": 9600.0
   }
  ]
 },
 {
  "paciente": "Sidnei Albano",
  "data_cirurgia": "2025-11-20",
  "hospital": "HSE",
  "medico": "Felipe Guilherme",
  "fornecedor": "Phisiomed",
  "numero_nf": "335.701",
  "valor_total": 10100.0,
  "data_nf": "2025-11-28",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-02-10",
    "valor": 2252.0
   },
   {
    "numero": 2,
    "vencimento": "2026-03-10",
    "valor": 2252.0
   },
   {
    "numero": 3,
    "vencimento": "2026-04-10",
    "valor": 2252.0
   },
   {
    "numero": 4,
    "vencimento": "2026-05-10",
    "valor": 2252.0
   }
  ]
 },
 {
  "paciente": "Cacilda da Silva Rufino",
  "data_cirurgia": "2025-12-18",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "4389",
  "valor_total": 1998.0,
  "data_nf": "2025-12-22",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-02-10",
    "valor": 1998.0
   }
  ]
 },
 {
  "paciente": "Lisete Flores Gomes Fernandes",
  "data_cirurgia": "2025-11-27",
  "hospital": "",
  "medico": "Alissom Zamara",
  "fornecedor": "Medic Solution",
  "numero_nf": "270.834",
  "valor_total": 7045.0,
  "data_nf": "2025-12-23",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-02-21",
    "valor": 3522.5
   },
   {
    "numero": 2,
    "vencimento": "2026-03-23",
    "valor": 3522.5
   }
  ]
 },
 {
  "paciente": "Alexandra Olivia da Silva Lopes",
  "data_cirurgia": "2025-10-01",
  "hospital": "",
  "medico": "",
  "fornecedor": "Isomedical",
  "numero_nf": "89.405",
  "valor_total": 4110.0,
  "data_nf": "2025-12-08",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-02-10",
    "valor": 4110.0
   }
  ]
 },
 {
  "paciente": "Samuel Ferreira da Silva",
  "data_cirurgia": "2025-12-01",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "4352",
  "valor_total": 16950.0,
  "data_nf": "2025-12-16",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-02-10",
    "valor": 4237.5
   },
   {
    "numero": 2,
    "vencimento": "2026-03-10",
    "valor": 4237.5
   },
   {
    "numero": 3,
    "vencimento": "2026-04-10",
    "valor": 4237.5
   },
   {
    "numero": 4,
    "vencimento": "2026-05-10",
    "valor": 4237.5
   }
  ]
 },
 {
  "paciente": "Douglas Leite Pereira",
  "data_cirurgia": "2025-11-24",
  "hospital": "HSE",
  "medico": "Carlo Alba",
  "fornecedor": "Itamedical",
  "numero_nf": "9159",
  "valor_total": 6200.0,
  "data_nf": "2025-11-25",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-10",
    "valor": 2066.67
   },
   {
    "numero": 3,
    "vencimento": "2026-04-10",
    "valor": 2066.67
   }
  ]
 },
 {
  "paciente": "Willians Santos Baracho",
  "data_cirurgia": "2025-12-02",
  "hospital": "",
  "medico": "",
  "fornecedor": "Millenium",
  "numero_nf": "17.553",
  "valor_total": 9400.0,
  "data_nf": "2025-12-05",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-10",
    "valor": 4700.0
   },
   {
    "numero": 2,
    "vencimento": "2026-04-10",
    "valor": 4700.0
   }
  ]
 },
 {
  "paciente": "Mariana Santos Nogueira",
  "data_cirurgia": "2025-12-08",
  "hospital": "",
  "medico": "",
  "fornecedor": "Custon Medical",
  "numero_nf": "7.602",
  "valor_total": 20700.0,
  "data_nf": "2025-12-08",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-10",
    "valor": 6900.0
   },
   {
    "numero": 2,
    "vencimento": "2026-04-10",
    "valor": 6900.0
   },
   {
    "numero": 3,
    "vencimento": "2026-05-10",
    "valor": 6900.0
   }
  ]
 },
 {
  "paciente": "Edinaldo Jose da Silva",
  "data_cirurgia": "2025-12-09",
  "hospital": "HSE",
  "medico": "Breno Faria",
  "fornecedor": "Leoni",
  "numero_nf": "17.597",
  "valor_total": 10100.0,
  "data_nf": "2025-12-12",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-10",
    "valor": 5050.0
   },
   {
    "numero": 2,
    "vencimento": "2026-04-10",
    "valor": 5050.0
   }
  ]
 },
 {
  "paciente": "Vera Lucia dos Santos",
  "data_cirurgia": "2025-12-09",
  "hospital": "HSE",
  "medico": "Breno Faria",
  "fornecedor": "Leoni",
  "numero_nf": "17.598",
  "valor_total": 10100.0,
  "data_nf": "2025-12-12",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-10",
    "valor": 5050.0
   },
   {
    "numero": 2,
    "vencimento": "2026-04-10",
    "valor": 5050.0
   }
  ]
 },
 {
  "paciente": "Anna Clara Marques Pereira",
  "data_cirurgia": "2025-12-08",
  "hospital": "",
  "medico": "Jose Marcelo",
  "fornecedor": "Storm",
  "numero_nf": "301.815",
  "valor_total": 2300.0,
  "data_nf": "2025-12-18",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-10",
    "valor": 1150.0
   },
   {
    "numero": 2,
    "vencimento": "2026-04-10",
    "valor": 1150.0
   }
  ]
 },
 {
  "paciente": "Bruna Peres de Paiva",
  "data_cirurgia": "2025-12-15",
  "hospital": "HSE",
  "medico": "Daniel Reis",
  "fornecedor": "Storm",
  "numero_nf": "301.791",
  "valor_total": 2300.0,
  "data_nf": "2025-12-17",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-10",
    "valor": 1150.0
   },
   {
    "numero": 2,
    "vencimento": "2026-04-10",
    "valor": 1150.0
   }
  ]
 },
 {
  "paciente": "Maria Tereza Araujo Vasques",
  "data_cirurgia": "2025-11-10",
  "hospital": "",
  "medico": "",
  "fornecedor": "Promedon",
  "numero_nf": "517871",
  "valor_total": 3960.0,
  "data_nf": "2025-12-22",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-10",
    "valor": 3960.0
   }
  ]
 },
 {
  "paciente": "Celia Maria da Silva Menezes",
  "data_cirurgia": "2025-12-23",
  "hospital": "",
  "medico": "Alissom Zamara",
  "fornecedor": "Plantão Médico",
  "numero_nf": "300.613",
  "valor_total": 9745.0,
  "data_nf": "2025-12-29",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-10",
    "valor": 3248.0
   },
   {
    "numero": 2,
    "vencimento": "2026-04-10",
    "valor": 3248.0
   },
   {
    "numero": 3,
    "vencimento": "2026-05-10",
    "valor": 3248.0
   }
  ]
 },
 {
  "paciente": "Ilma Ribeiro da Silva Ferreira",
  "data_cirurgia": "2025-12-29",
  "hospital": "",
  "medico": "",
  "fornecedor": "Leoni",
  "numero_nf": "17.657",
  "valor_total": 9400.0,
  "data_nf": "2026-01-05",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-10",
    "valor": 4700.0
   },
   {
    "numero": 2,
    "vencimento": "2026-04-10",
    "valor": 4700.0
   }
  ]
 },
 {
  "paciente": "Celso Aparecido Campos",
  "data_cirurgia": "2025-12-26",
  "hospital": "",
  "medico": "Matheus Bastos",
  "fornecedor": "RL Medical",
  "numero_nf": "517",
  "valor_total": 800.0,
  "data_nf": "2026-01-06",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-10",
    "valor": 800.0
   }
  ]
 },
 {
  "paciente": "Elza Alves Bergamasco",
  "data_cirurgia": "2025-12-24",
  "hospital": "",
  "medico": "",
  "fornecedor": "Leoni",
  "numero_nf": "17.677",
  "valor_total": 9100.0,
  "data_nf": "2026-01-08",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-10",
    "valor": 4550.0
   },
   {
    "numero": 2,
    "vencimento": "2026-04-10",
    "valor": 4550.0
   }
  ]
 },
 {
  "paciente": "Rafael Rodrigues Froio",
  "data_cirurgia": "2025-12-13",
  "hospital": "",
  "medico": "",
  "fornecedor": "Russer",
  "numero_nf": "106.776",
  "valor_total": 2205.0,
  "data_nf": "2026-01-08",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-10",
    "valor": 1102.5
   },
   {
    "numero": 2,
    "vencimento": "2026-04-10",
    "valor": 1102.5
   }
  ]
 },
 {
  "paciente": "Hevellin de Andrade Alves",
  "data_cirurgia": "2025-12-16",
  "hospital": "HSE",
  "medico": "Carlo Alba",
  "fornecedor": "Itamedical",
  "numero_nf": "9.345",
  "valor_total": 3500.0,
  "data_nf": "2026-01-05",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-10",
    "valor": 1750.0
   },
   {
    "numero": 2,
    "vencimento": "2026-04-10",
    "valor": 1750.0
   }
  ]
 },
 {
  "paciente": "Iara Palasson Garcia",
  "data_cirurgia": "2025-10-14",
  "hospital": "",
  "medico": "",
  "fornecedor": "E.tamussino",
  "numero_nf": "272930",
  "valor_total": 1400.0,
  "data_nf": "2025-12-19",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-10",
    "valor": 1400.0
   }
  ]
 },
 {
  "paciente": "Vivian Silva Cursino",
  "data_cirurgia": "2025-12-19",
  "hospital": "HSE",
  "medico": "Erica Gerard",
  "fornecedor": "Slim",
  "numero_nf": "34.859",
  "valor_total": 1155.0,
  "data_nf": "2025-12-29",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-10",
    "valor": 1155.0
   }
  ]
 },
 {
  "paciente": "David Mendes dos Santos Filho",
  "data_cirurgia": "2025-12-18",
  "hospital": "HSE",
  "medico": "Ederson Raimundo",
  "fornecedor": "Slim",
  "numero_nf": "34.863",
  "valor_total": 1155.0,
  "data_nf": "2025-12-30",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-10",
    "valor": 1155.0
   }
  ]
 },
 {
  "paciente": "Lais Augusto Coelho Nantes Lima",
  "data_cirurgia": "2025-12-25",
  "hospital": "HSE",
  "medico": "Felipe Guilherme",
  "fornecedor": "Phisiomed",
  "numero_nf": "337.069",
  "valor_total": 9500.0,
  "data_nf": "2025-12-05",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-10",
    "valor": 316664.0
   },
   {
    "numero": 2,
    "vencimento": "2026-04-10",
    "valor": 316664.0
   },
   {
    "numero": 3,
    "vencimento": "2026-05-10",
    "valor": 316664.0
   }
  ]
 },
 {
  "paciente": "Celia Maria da Silva Menezes",
  "data_cirurgia": "2026-01-13",
  "hospital": "São Lucas Santos",
  "medico": "Alissom Zamara",
  "fornecedor": "Plantão Médico",
  "numero_nf": "303.178",
  "valor_total": 9745.0,
  "data_nf": "2026-01-27",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-10",
    "valor": 3248.0
   },
   {
    "numero": 2,
    "vencimento": "2026-04-10",
    "valor": 3248.0
   },
   {
    "numero": 3,
    "vencimento": "2026-05-10",
    "valor": 3249.0
   }
  ]
 },
 {
  "paciente": "Lourdes Senna Goncalves",
  "data_cirurgia": "2026-01-10",
  "hospital": "",
  "medico": "Matheus Bastos",
  "fornecedor": "Wolford Medical",
  "numero_nf": "16.573",
  "valor_total": 13550.0,
  "data_nf": "2026-01-28",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-20",
    "valor": 4516.62
   },
   {
    "numero": 2,
    "vencimento": "2026-04-20",
    "valor": 4516.62
   },
   {
    "numero": 3,
    "vencimento": "2026-05-20",
    "valor": 4516.62
   }
  ]
 },
 {
  "paciente": "Nilton Cota",
  "data_cirurgia": "2025-08-25",
  "hospital": "HSE",
  "medico": "Luis Felipe",
  "fornecedor": "OTN Surgical",
  "numero_nf": "21830",
  "valor_total": 8900.0,
  "data_nf": "2026-01-28",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-10",
    "valor": 2225.0
   },
   {
    "numero": 2,
    "vencimento": "2026-04-10",
    "valor": 2225.0
   },
   {
    "numero": 3,
    "vencimento": "2026-05-10",
    "valor": 2225.0
   },
   {
    "numero": 4,
    "vencimento": "2026-06-10",
    "valor": 2225.0
   }
  ]
 },
 {
  "paciente": "Maria Lucia Rodrigues Santos",
  "data_cirurgia": "2026-01-06",
  "hospital": "",
  "medico": "",
  "fornecedor": "Russer",
  "numero_nf": "107.532",
  "valor_total": 3095.0,
  "data_nf": "2026-01-29",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-30",
    "valor": 1547.5
   },
   {
    "numero": 2,
    "vencimento": "2026-04-30",
    "valor": 1547.5
   }
  ]
 },
 {
  "paciente": "Leide Aparecida dos Santos Orti",
  "data_cirurgia": "2026-01-17",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "4581",
  "valor_total": 16950.0,
  "data_nf": "2026-01-30",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-10",
    "valor": 4237.5
   },
   {
    "numero": 2,
    "vencimento": "2026-04-10",
    "valor": 4237.5
   },
   {
    "numero": 3,
    "vencimento": "2026-05-10",
    "valor": 4237.5
   },
   {
    "numero": 4,
    "vencimento": "2026-06-10",
    "valor": 4237.5
   }
  ]
 },
 {
  "paciente": "Marina Severina Alves Vairo",
  "data_cirurgia": "2026-01-19",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "4557",
  "valor_total": 16950.0,
  "data_nf": "2026-01-29",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-10",
    "valor": 4237.5
   },
   {
    "numero": 2,
    "vencimento": "2026-04-10",
    "valor": 4237.5
   },
   {
    "numero": 3,
    "vencimento": "2026-05-10",
    "valor": 4237.5
   },
   {
    "numero": 4,
    "vencimento": "2026-06-10",
    "valor": 4237.5
   }
  ]
 },
 {
  "paciente": "Marcelo Rodrigues do Nascimento",
  "data_cirurgia": "2026-01-05",
  "hospital": "HSE",
  "medico": "Alexandre Vianna",
  "fornecedor": "Surgipro",
  "numero_nf": "326.944",
  "valor_total": 8040.0,
  "data_nf": "2026-01-29",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-10",
    "valor": 2010.0
   },
   {
    "numero": 2,
    "vencimento": "2026-04-10",
    "valor": 2010.0
   },
   {
    "numero": 3,
    "vencimento": "2026-05-10",
    "valor": 2010.0
   },
   {
    "numero": 4,
    "vencimento": "2026-06-10",
    "valor": 2010.0
   }
  ]
 },
 {
  "paciente": "Erick Alecsander Pereira Pandalpho",
  "data_cirurgia": "2026-01-10",
  "hospital": "HSE",
  "medico": "Jose Luis",
  "fornecedor": "Surgipro",
  "numero_nf": "33",
  "valor_total": 2000.0,
  "data_nf": "2026-01-29",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-10",
    "valor": 1000.0
   },
   {
    "numero": 2,
    "vencimento": "2026-04-10",
    "valor": 1000.0
   }
  ]
 },
 {
  "paciente": "Christhianne Arantes D. B. dos Santos",
  "data_cirurgia": "2026-01-29",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "4593",
  "valor_total": 16950.0,
  "data_nf": "2026-02-03",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-10",
    "valor": 4237.5
   },
   {
    "numero": 2,
    "vencimento": "2026-04-10",
    "valor": 4237.5
   },
   {
    "numero": 3,
    "vencimento": "2026-05-10",
    "valor": 4237.5
   },
   {
    "numero": 4,
    "vencimento": "2026-06-10",
    "valor": 4237.5
   }
  ]
 },
 {
  "paciente": "Charles Nunes de Oliveira",
  "data_cirurgia": "2025-11-27",
  "hospital": "",
  "medico": "",
  "fornecedor": "Russer",
  "numero_nf": "107.232",
  "valor_total": 3095.0,
  "data_nf": "2026-01-22",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-10",
    "valor": 1547.5
   },
   {
    "numero": 2,
    "vencimento": "2026-04-10",
    "valor": 1547.5
   }
  ]
 },
 {
  "paciente": "Julio Cesar de Carlos",
  "data_cirurgia": "2025-08-15",
  "hospital": "",
  "medico": "",
  "fornecedor": "Russer",
  "numero_nf": "107.744",
  "valor_total": 2305.0,
  "data_nf": "2026-02-05",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-23",
    "valor": 2305.0
   }
  ]
 },
 {
  "paciente": "Vanessa Baptista Valentim da Silva",
  "data_cirurgia": "2026-01-16",
  "hospital": "",
  "medico": "",
  "fornecedor": "Russer",
  "numero_nf": "107.748",
  "valor_total": 3240.0,
  "data_nf": "2026-02-05",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-23",
    "valor": 3240.0
   }
  ]
 },
 {
  "paciente": "Monica Alves dos Santos Oliveira",
  "data_cirurgia": "2025-10-31",
  "hospital": "",
  "medico": "",
  "fornecedor": "Mogami",
  "numero_nf": "56.376",
  "valor_total": 1390.0,
  "data_nf": "2026-02-06",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-06",
    "valor": 1390.0
   }
  ]
 },
 {
  "paciente": "Maria de Fatima Souza Silva",
  "data_cirurgia": "2026-02-05",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "4613",
  "valor_total": 16950.0,
  "data_nf": "2026-02-09",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-10",
    "valor": 4237.5
   },
   {
    "numero": 2,
    "vencimento": "2026-04-10",
    "valor": 4237.5
   },
   {
    "numero": 3,
    "vencimento": "2026-05-10",
    "valor": 4237.5
   },
   {
    "numero": 4,
    "vencimento": "2026-06-10",
    "valor": 4237.5
   }
  ]
 },
 {
  "paciente": "Alessandre Jorge Chiapetta",
  "data_cirurgia": "2025-11-04",
  "hospital": "",
  "medico": "",
  "fornecedor": "Mogami",
  "numero_nf": "56.673",
  "valor_total": 1900.0,
  "data_nf": "2026-02-09",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-11",
    "valor": 1900.0
   }
  ]
 },
 {
  "paciente": "Ricardo Ferreira da Silva",
  "data_cirurgia": "2026-01-13",
  "hospital": "HSE",
  "medico": "Wagner Riva",
  "fornecedor": "Isomedical",
  "numero_nf": "92.591",
  "valor_total": 1500.0,
  "data_nf": "2026-02-12",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-30",
    "valor": 1500.0
   }
  ]
 },
 {
  "paciente": "Luiz Antonio Martins",
  "data_cirurgia": "2026-01-15",
  "hospital": "",
  "medico": "",
  "fornecedor": "Isomedical",
  "numero_nf": "92.664",
  "valor_total": 267.0,
  "data_nf": "2026-02-13",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-03-30",
    "valor": 267.0
   }
  ]
 },
 {
  "paciente": "Carla Regina Novaes Arruda",
  "data_cirurgia": "2026-01-16",
  "hospital": "HSE",
  "medico": "Ittalo Henrique",
  "fornecedor": "Handle",
  "numero_nf": "1551.429",
  "valor_total": 2020.0,
  "data_nf": "2026-01-30",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-04-10",
    "valor": 505.0
   },
   {
    "numero": 2,
    "vencimento": "2026-05-10",
    "valor": 505.0
   },
   {
    "numero": 3,
    "vencimento": "2026-06-10",
    "valor": 505.0
   },
   {
    "numero": 4,
    "vencimento": "2026-07-10",
    "valor": 505.0
   }
  ]
 },
 {
  "paciente": "Dennys Willian Conceição Costa",
  "data_cirurgia": "2025-08-27",
  "hospital": "",
  "medico": "Behramann Martins",
  "fornecedor": "Micromedical",
  "numero_nf": "69614",
  "valor_total": 14841.0,
  "data_nf": "2026-01-30",
  "total_parcelas": 6,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-04-10",
    "valor": 2473.5
   },
   {
    "numero": 2,
    "vencimento": "2026-05-10",
    "valor": 2473.5
   },
   {
    "numero": 3,
    "vencimento": "2026-06-10",
    "valor": 2473.5
   },
   {
    "numero": 4,
    "vencimento": "2026-07-10",
    "valor": 2473.5
   },
   {
    "numero": 6,
    "vencimento": "2026-08-10",
    "valor": 2473.5
   },
   {
    "numero": 6,
    "vencimento": "2026-09-10",
    "valor": 2473.5
   }
  ]
 },
 {
  "paciente": "Helena Alambert",
  "data_cirurgia": "2026-01-21",
  "hospital": "",
  "medico": "Luis Felipe",
  "fornecedor": "Leoni",
  "numero_nf": "17.785",
  "valor_total": 7100.0,
  "data_nf": "2026-02-04",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-04-10",
    "valor": 3550.0
   },
   {
    "numero": 2,
    "vencimento": "2026-05-10",
    "valor": 3550.0
   }
  ]
 },
 {
  "paciente": "Raimundo Osvanir Queiroz do Nascimento",
  "data_cirurgia": "2026-01-10",
  "hospital": "",
  "medico": "Jefferson do Santos",
  "fornecedor": "Biotronik",
  "numero_nf": "1163173",
  "valor_total": 8800.0,
  "data_nf": "2026-02-03",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-04-10",
    "valor": 293330.0
   },
   {
    "numero": 1,
    "vencimento": "2026-05-10",
    "valor": 2933.3
   },
   {
    "numero": 2,
    "vencimento": "2026-06-10",
    "valor": 2933.3
   },
   {
    "numero": 3,
    "vencimento": "2026-07-10",
    "valor": 2933.3
   }
  ]
 },
 {
  "paciente": "Lindinaldo Cordeiro de Andrade",
  "data_cirurgia": "2026-01-27",
  "hospital": "",
  "medico": "",
  "fornecedor": "Leoni",
  "numero_nf": "17786",
  "valor_total": 9400.0,
  "data_nf": "2026-02-04",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-04-10",
    "valor": 4700.0
   },
   {
    "numero": 1,
    "vencimento": "2026-05-10",
    "valor": 4700.0
   },
   {
    "numero": 2,
    "vencimento": "2026-06-10",
    "valor": 4700.0
   }
  ]
 },
 {
  "paciente": "Leticia Santos de Souza",
  "data_cirurgia": "2025-12-10",
  "hospital": "",
  "medico": "",
  "fornecedor": "Mogani",
  "numero_nf": "56450",
  "valor_total": 7460.0,
  "data_nf": "2026-02-09",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-04-10",
    "valor": 1865.0
   },
   {
    "numero": 2,
    "vencimento": "2026-05-10",
    "valor": 1865.0
   },
   {
    "numero": 3,
    "vencimento": "2026-06-10",
    "valor": 1865.0
   },
   {
    "numero": 4,
    "vencimento": "2026-07-10",
    "valor": 1865.0
   }
  ]
 },
 {
  "paciente": "Neuza Gonçalves Lima",
  "data_cirurgia": "2025-10-17",
  "hospital": "",
  "medico": "",
  "fornecedor": "Russer",
  "numero_nf": "107835",
  "valor_total": 2135.0,
  "data_nf": "2026-02-09",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-04-10",
    "valor": 2135.0
   }
  ]
 },
 {
  "paciente": "Alda Fonseca Guimarães",
  "data_cirurgia": "2026-01-06",
  "hospital": "",
  "medico": "",
  "fornecedor": "Russer",
  "numero_nf": "107842",
  "valor_total": 1245.0,
  "data_nf": "2026-02-09",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-04-10",
    "valor": 1245.0
   }
  ]
 },
 {
  "paciente": "João Figueira de Maria Filho",
  "data_cirurgia": "2026-01-26",
  "hospital": "",
  "medico": "",
  "fornecedor": "Surgipro",
  "numero_nf": "36",
  "valor_total": 1000.0,
  "data_nf": "2026-02-10",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-04-10",
    "valor": 1000.0
   }
  ]
 },
 {
  "paciente": "Marta Rodrigues Ferreira",
  "data_cirurgia": "2026-02-06",
  "hospital": "",
  "medico": "",
  "fornecedor": "Biotronik",
  "numero_nf": "1164471",
  "valor_total": 8800.0,
  "data_nf": "2026-02-12",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-04-10",
    "valor": 2933.3
   },
   {
    "numero": 2,
    "vencimento": "2026-05-10",
    "valor": 2933.3
   },
   {
    "numero": 3,
    "vencimento": "2026-06-10",
    "valor": 2933.4
   }
  ]
 },
 {
  "paciente": "Leonardo Brambilla Nicolaci Fincatti",
  "data_cirurgia": "2025-09-15",
  "hospital": "",
  "medico": "",
  "fornecedor": "OTN Surgical",
  "numero_nf": "21917",
  "valor_total": 20000.0,
  "data_nf": "2026-02-11",
  "total_parcelas": 5,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-04-10",
    "valor": 4000.0
   },
   {
    "numero": 2,
    "vencimento": "2026-05-10",
    "valor": 4000.0
   },
   {
    "numero": 3,
    "vencimento": "2026-06-10",
    "valor": 4000.0
   },
   {
    "numero": 4,
    "vencimento": "2026-07-10",
    "valor": 4000.0
   },
   {
    "numero": 5,
    "vencimento": "2026-08-10",
    "valor": 4000.0
   }
  ]
 },
 {
  "paciente": "Josefa Pereira da Silva dos Santos",
  "data_cirurgia": "2026-01-07",
  "hospital": "",
  "medico": "",
  "fornecedor": "Surgipro",
  "numero_nf": "35",
  "valor_total": 7090.0,
  "data_nf": "2026-02-10",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-04-10",
    "valor": 1772.5
   },
   {
    "numero": 2,
    "vencimento": "2026-05-10",
    "valor": 1772.5
   },
   {
    "numero": 3,
    "vencimento": "2026-06-10",
    "valor": 1772.5
   },
   {
    "numero": 4,
    "vencimento": "2026-07-10",
    "valor": 1772.5
   }
  ]
 },
 {
  "paciente": "Marcos Antonio de Oliveira",
  "data_cirurgia": "2026-02-06",
  "hospital": "",
  "medico": "",
  "fornecedor": "Russer",
  "numero_nf": "108.344",
  "valor_total": 1250.0,
  "data_nf": "2026-02-25",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-04-10",
    "valor": 1250.0
   }
  ]
 },
 {
  "paciente": "Wagner Rodrigues Moraes",
  "data_cirurgia": "2026-02-11",
  "hospital": "",
  "medico": "",
  "fornecedor": "Leoni",
  "numero_nf": "17.908",
  "valor_total": 12000.0,
  "data_nf": "2026-02-25",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-04-10",
    "valor": 4000.0
   },
   {
    "numero": 2,
    "vencimento": "2026-05-10",
    "valor": 4000.0
   },
   {
    "numero": 3,
    "vencimento": "2026-06-10",
    "valor": 4000.0
   }
  ]
 },
 {
  "paciente": "Celia Costa Saldanha",
  "data_cirurgia": "2026-02-18",
  "hospital": "",
  "medico": "",
  "fornecedor": "Leoni",
  "numero_nf": "17.909",
  "valor_total": 19000.0,
  "data_nf": "2026-02-25",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-04-10",
    "valor": 6334.6
   },
   {
    "numero": 2,
    "vencimento": "2026-05-10",
    "valor": 6332.7
   },
   {
    "numero": 3,
    "vencimento": "2026-06-10",
    "valor": 6332.7
   }
  ]
 },
 {
  "paciente": "Juliana da Conceição Costa Pinheiro",
  "data_cirurgia": "2026-02-14",
  "hospital": "",
  "medico": "",
  "fornecedor": "Itamedical",
  "numero_nf": "9589",
  "valor_total": 4500.0,
  "data_nf": "2026-02-25",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-04-10",
    "valor": 1500.0
   },
   {
    "numero": 2,
    "vencimento": "2026-05-10",
    "valor": 1500.0
   },
   {
    "numero": 3,
    "vencimento": "2026-06-10",
    "valor": 1500.0
   }
  ]
 },
 {
  "paciente": "Nair Vilella Gaiva Victoriano",
  "data_cirurgia": "2026-02-25",
  "hospital": "",
  "medico": "NF cancelada",
  "fornecedor": "Lifesil",
  "numero_nf": "2584",
  "valor_total": 2890.0,
  "data_nf": "2026-02-19",
  "total_parcelas": 1,
  "situacao": "cancelada",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": []
 },
 {
  "paciente": "Nair Vilella Gaiva Victoriano",
  "data_cirurgia": "2026-02-25",
  "hospital": "",
  "medico": "",
  "fornecedor": "Brsil",
  "numero_nf": "3179",
  "valor_total": 2890.0,
  "data_nf": "2026-03-27",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 2,
    "vencimento": "2026-04-26",
    "valor": 1445.0
   },
   {
    "numero": 2,
    "vencimento": "2026-05-26",
    "valor": 1445.0
   }
  ]
 },
 {
  "paciente": "Sidnei Albano",
  "data_cirurgia": "2025-11-20",
  "hospital": "",
  "medico": "",
  "fornecedor": "Mogami",
  "numero_nf": "62.567",
  "valor_total": 109.0,
  "data_nf": "2026-03-02",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-04-30",
    "valor": 109.0
   }
  ]
 },
 {
  "paciente": "João Carlos Costa",
  "data_cirurgia": "2026-01-29",
  "hospital": "",
  "medico": "",
  "fornecedor": "Mogami",
  "numero_nf": "62.744",
  "valor_total": 8334.0,
  "data_nf": "2026-03-02",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-04-01",
    "valor": 2083.5
   },
   {
    "numero": 2,
    "vencimento": "2026-05-01",
    "valor": 2083.5
   },
   {
    "numero": 3,
    "vencimento": "2026-06-01",
    "valor": 2083.5
   },
   {
    "numero": 4,
    "vencimento": "2026-06-30",
    "valor": 2083.5
   }
  ]
 },
 {
  "paciente": "Diogo dos Santos Barbosa",
  "data_cirurgia": "2026-02-15",
  "hospital": "",
  "medico": "",
  "fornecedor": "RL Medical",
  "numero_nf": "547",
  "valor_total": 1600.0,
  "data_nf": "2026-02-27",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-04-10",
    "valor": 800.0
   },
   {
    "numero": 2,
    "vencimento": "2026-05-11",
    "valor": 800.0
   }
  ]
 },
 {
  "paciente": "Atila Gregório Pereira",
  "data_cirurgia": "2026-01-14",
  "hospital": "",
  "medico": "",
  "fornecedor": "Isomedical",
  "numero_nf": "94.759",
  "valor_total": 3230.0,
  "data_nf": "2026-03-23",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-04-10",
    "valor": 1615.0
   },
   {
    "numero": 2,
    "vencimento": "2026-05-10",
    "valor": 1615.0
   }
  ]
 },
 {
  "paciente": "Rosilda Rodrigues de Carvalho",
  "data_cirurgia": "2026-01-21",
  "hospital": "",
  "medico": "Behramann Martins",
  "fornecedor": "Micromedical",
  "numero_nf": "70740",
  "valor_total": 37894.0,
  "data_nf": "2026-03-12",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-04-11",
    "valor": 9743.5
   },
   {
    "numero": 2,
    "vencimento": "2026-05-11",
    "valor": 9743.5
   },
   {
    "numero": 3,
    "vencimento": "2026-06-10",
    "valor": 9743.5
   },
   {
    "numero": 4,
    "vencimento": "2026-07-10",
    "valor": 9743.5
   }
  ]
 },
 {
  "paciente": "Gabriel dos Santos da Silva",
  "data_cirurgia": "2026-02-18",
  "hospital": "HSE",
  "medico": "Igor Marijuschkim",
  "fornecedor": "Gm Reis",
  "numero_nf": "40.565",
  "valor_total": 12890.0,
  "data_nf": "2026-03-19",
  "total_parcelas": 5,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-04-18",
    "valor": 2596.16
   },
   {
    "numero": 2,
    "vencimento": "2026-05-18",
    "valor": 2596.16
   },
   {
    "numero": 3,
    "vencimento": "2026-06-17",
    "valor": 2596.16
   },
   {
    "numero": 4,
    "vencimento": "2026-07-17",
    "valor": 2596.16
   },
   {
    "numero": 5,
    "vencimento": "2026-08-16",
    "valor": 2596.16
   }
  ]
 },
 {
  "paciente": "Amanda Soares da Silva",
  "data_cirurgia": "2026-01-19",
  "hospital": "",
  "medico": "",
  "fornecedor": "Storm",
  "numero_nf": "302251",
  "valor_total": 1600.0,
  "data_nf": "2026-02-02",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-04-20",
    "valor": 800.0
   },
   {
    "numero": 2,
    "vencimento": "2026-05-10",
    "valor": 800.0
   }
  ]
 },
 {
  "paciente": "Claudia Aparecida Fuchs Pereira Silva",
  "data_cirurgia": "2026-02-10",
  "hospital": "HSE",
  "medico": "Breno Faria",
  "fornecedor": "Leoni",
  "numero_nf": "17.838",
  "valor_total": 10100.0,
  "data_nf": "2026-02-12",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-05-10",
    "valor": 5050.0
   },
   {
    "numero": 2,
    "vencimento": "2026-06-10",
    "valor": 5050.0
   }
  ]
 },
 {
  "paciente": "Sergio Luiz Gugel",
  "data_cirurgia": "2026-02-10",
  "hospital": "",
  "medico": "",
  "fornecedor": "Leoni",
  "numero_nf": "17.839",
  "valor_total": 10100.0,
  "data_nf": "2026-02-12",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-05-10",
    "valor": 5050.0
   },
   {
    "numero": 2,
    "vencimento": "2026-06-10",
    "valor": 5050.0
   }
  ]
 },
 {
  "paciente": "Ravelly Maria Siqueira Vera",
  "data_cirurgia": "2026-02-11",
  "hospital": "",
  "medico": "",
  "fornecedor": "Millenium",
  "numero_nf": "4651",
  "valor_total": 999.0,
  "data_nf": "2026-02-19",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-05-10",
    "valor": 999.0
   }
  ]
 },
 {
  "paciente": "Anna Karolina Pereira Oliveira",
  "data_cirurgia": "2026-02-25",
  "hospital": "HSE",
  "medico": "Luis Felipe",
  "fornecedor": "Leoni",
  "numero_nf": "17.924",
  "valor_total": 9000.0,
  "data_nf": "2026-03-05",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-05-10",
    "valor": 4500.0
   },
   {
    "numero": 2,
    "vencimento": "2026-06-10",
    "valor": 4500.0
   }
  ]
 },
 {
  "paciente": "Josefa Rodrigues da Costa",
  "data_cirurgia": "2026-02-23",
  "hospital": "HSE",
  "medico": "Luis Felipe",
  "fornecedor": "Surgipro",
  "numero_nf": "48",
  "valor_total": 4000.0,
  "data_nf": "2026-03-04",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-05-10",
    "valor": 1333.34
   },
   {
    "numero": 2,
    "vencimento": "2026-06-10",
    "valor": 1333.34
   },
   {
    "numero": 3,
    "vencimento": "2026-07-10",
    "valor": 1333.34
   }
  ]
 },
 {
  "paciente": "Thamara de Oliveira Santana do Carmo",
  "data_cirurgia": "2026-02-23",
  "hospital": "",
  "medico": "",
  "fornecedor": "Mogami",
  "numero_nf": "62.783",
  "valor_total": 7460.0,
  "data_nf": "2026-03-02",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-05-10",
    "valor": 1865.0
   },
   {
    "numero": 2,
    "vencimento": "2026-06-10",
    "valor": 1865.0
   },
   {
    "numero": 3,
    "vencimento": "2026-07-10",
    "valor": 1865.0
   },
   {
    "numero": 4,
    "vencimento": "2026-08-10",
    "valor": 1865.0
   }
  ]
 },
 {
  "paciente": "Rosangela Cortez Alves",
  "data_cirurgia": "2026-01-05",
  "hospital": "",
  "medico": "",
  "fornecedor": "Mogami",
  "numero_nf": "62.781",
  "valor_total": 1350.0,
  "data_nf": "2026-03-02",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-05-11",
    "valor": 675.0
   },
   {
    "numero": 2,
    "vencimento": "2026-06-10",
    "valor": 675.0
   }
  ]
 },
 {
  "paciente": "Ojair Luiz",
  "data_cirurgia": "2026-02-23",
  "hospital": "",
  "medico": "",
  "fornecedor": "Millenium",
  "numero_nf": "4722",
  "valor_total": 16950.0,
  "data_nf": "2026-03-10",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-05-10",
    "valor": 4237.5
   },
   {
    "numero": 2,
    "vencimento": "2026-06-10",
    "valor": 4237.5
   },
   {
    "numero": 3,
    "vencimento": "2026-07-10",
    "valor": 4237.5
   },
   {
    "numero": 4,
    "vencimento": "2026-08-10",
    "valor": 4237.5
   }
  ]
 },
 {
  "paciente": "Wesley Gonçalves",
  "data_cirurgia": "2026-02-26",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "4752",
  "valor_total": 16950.0,
  "data_nf": "2026-03-16",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-05-10",
    "valor": 4237.5
   },
   {
    "numero": 2,
    "vencimento": "2026-06-10",
    "valor": 4237.5
   },
   {
    "numero": 3,
    "vencimento": "2026-07-10",
    "valor": 4237.5
   },
   {
    "numero": 4,
    "vencimento": "2026-08-10",
    "valor": 4237.5
   }
  ]
 },
 {
  "paciente": "Anderson de Oliveira Carvalho",
  "data_cirurgia": "2026-03-04",
  "hospital": "HSE",
  "medico": "Luis Felipe",
  "fornecedor": "Leoni",
  "numero_nf": "17.967",
  "valor_total": 19000.0,
  "data_nf": "2026-03-17",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-05-10",
    "valor": 6332.7
   },
   {
    "numero": 2,
    "vencimento": "2026-06-10",
    "valor": 6332.7
   },
   {
    "numero": 3,
    "vencimento": "2026-07-10",
    "valor": 6332.7
   }
  ]
 },
 {
  "paciente": "Inacia Fortunato Silva de Jesus",
  "data_cirurgia": "2026-03-05",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Surgipro",
  "numero_nf": "56",
  "valor_total": 2000.0,
  "data_nf": "2026-03-16",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-05-10",
    "valor": 1000.0
   },
   {
    "numero": 2,
    "vencimento": "2026-06-10",
    "valor": 1000.0
   }
  ]
 },
 {
  "paciente": "Rita de Cassia Castilha dos Santos",
  "data_cirurgia": "2026-03-05",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Surgipro",
  "numero_nf": "55",
  "valor_total": 2000.0,
  "data_nf": "2026-03-16",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-05-10",
    "valor": 1000.0
   },
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 1000.0
   }
  ]
 },
 {
  "paciente": "Manoel Edmilton da Silva",
  "data_cirurgia": "2026-03-11",
  "hospital": "São Lucas Santos",
  "medico": "Acacio Fernandes",
  "fornecedor": "Micromedical",
  "numero_nf": "70979",
  "valor_total": 5179.0,
  "data_nf": "2026-03-20",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-05-10",
    "valor": 1726.33
   },
   {
    "numero": 2,
    "vencimento": "2026-06-10",
    "valor": 1726.33
   },
   {
    "numero": 3,
    "vencimento": "2026-07-10",
    "valor": 1726.33
   }
  ]
 },
 {
  "paciente": "Carlos Alberto Alves Lourenço",
  "data_cirurgia": "2026-03-09",
  "hospital": "HSE",
  "medico": "Carlo Alba",
  "fornecedor": "Itamedical",
  "numero_nf": "9.731",
  "valor_total": 4500.0,
  "data_nf": "2026-03-24",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-05-10",
    "valor": 1500.0
   },
   {
    "numero": 2,
    "vencimento": "2026-06-10",
    "valor": 1500.0
   },
   {
    "numero": 3,
    "vencimento": "2026-07-10",
    "valor": 1500.0
   }
  ]
 },
 {
  "paciente": "Celeste Santos Paixão",
  "data_cirurgia": "2026-03-09",
  "hospital": "HSE",
  "medico": "Carlo Alba",
  "fornecedor": "Itamedical",
  "numero_nf": "9.730",
  "valor_total": 4500.0,
  "data_nf": "2026-03-24",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-05-10",
    "valor": 1500.0
   },
   {
    "numero": 2,
    "vencimento": "2026-06-10",
    "valor": 1500.0
   },
   {
    "numero": 3,
    "vencimento": "2026-07-10",
    "valor": 1500.0
   }
  ]
 },
 {
  "paciente": "Maria de Fátima Vieira Sales Evaristo",
  "data_cirurgia": "2026-03-09",
  "hospital": "",
  "medico": "",
  "fornecedor": "Mogami",
  "numero_nf": "69.271",
  "valor_total": 1589.0,
  "data_nf": "2026-03-24",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-05-10",
    "valor": 794.5
   },
   {
    "numero": 2,
    "vencimento": "2026-06-10",
    "valor": 794.5
   }
  ]
 },
 {
  "paciente": "Elaine Christine de Souza",
  "data_cirurgia": "2026-03-05",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "4814",
  "valor_total": 16950.0,
  "data_nf": "2026-03-25",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-05-10",
    "valor": 4237.5
   },
   {
    "numero": 2,
    "vencimento": "2026-06-10",
    "valor": 4237.5
   },
   {
    "numero": 3,
    "vencimento": "2026-07-10",
    "valor": 4237.5
   },
   {
    "numero": 4,
    "vencimento": "2026-08-10",
    "valor": 4237.5
   }
  ]
 },
 {
  "paciente": "Sandra Mota da Silva Souza",
  "data_cirurgia": "2026-03-06",
  "hospital": "",
  "medico": "",
  "fornecedor": "Russer",
  "numero_nf": "109.505",
  "valor_total": 3095.0,
  "data_nf": "2026-03-25",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-05-11",
    "valor": 3095.0
   }
  ]
 },
 {
  "paciente": "Elizabete Oliveira de Souza",
  "data_cirurgia": "2026-02-06",
  "hospital": "",
  "medico": "",
  "fornecedor": "Mogami",
  "numero_nf": "70124",
  "valor_total": 109.0,
  "data_nf": "2026-03-26",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-05-04",
    "valor": 36.33
   },
   {
    "numero": 2,
    "vencimento": "2026-06-10",
    "valor": 36.33
   },
   {
    "numero": 3,
    "vencimento": "2026-07-01",
    "valor": 36.33
   }
  ]
 },
 {
  "paciente": "Raphael de Oliveira Lacalentola",
  "data_cirurgia": "2026-03-06",
  "hospital": "",
  "medico": "",
  "fornecedor": "Russer",
  "numero_nf": "109.600",
  "valor_total": 2205.0,
  "data_nf": "2026-03-27",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-05-11",
    "valor": 2205.0
   }
  ]
 },
 {
  "paciente": "Marlucy da Silva Fernandes",
  "data_cirurgia": "2026-03-16",
  "hospital": "HSE",
  "medico": "Luis Felipe",
  "fornecedor": "Surgipro",
  "numero_nf": "60",
  "valor_total": 5500.0,
  "data_nf": "2026-03-27",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-05-10",
    "valor": 1833.34
   },
   {
    "numero": 2,
    "vencimento": "2026-06-10",
    "valor": 1833.34
   },
   {
    "numero": 3,
    "vencimento": "2026-07-10",
    "valor": 1833.34
   }
  ]
 },
 {
  "paciente": "Marcio Acioly dos Santos",
  "data_cirurgia": "2026-03-30",
  "hospital": "HSE",
  "medico": "Carlo Alba",
  "fornecedor": "Itamedical",
  "numero_nf": "9.755",
  "valor_total": 4500.0,
  "data_nf": "2026-03-31",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-05-10",
    "valor": 1125.0
   },
   {
    "numero": 2,
    "vencimento": "2026-06-10",
    "valor": 1125.0
   },
   {
    "numero": 3,
    "vencimento": "2026-07-10",
    "valor": 1125.0
   },
   {
    "numero": 4,
    "vencimento": "2026-08-10",
    "valor": 1125.0
   }
  ]
 },
 {
  "paciente": "Oscar Correia de Lima",
  "data_cirurgia": "2026-03-27",
  "hospital": "",
  "medico": "Matheus Bastos",
  "fornecedor": "RL Medical",
  "numero_nf": "557",
  "valor_total": 4150.0,
  "data_nf": "2026-04-02",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-05-10",
    "valor": 1383.32
   },
   {
    "numero": 2,
    "vencimento": "2026-06-10",
    "valor": 1383.32
   },
   {
    "numero": 3,
    "vencimento": "2026-07-10",
    "valor": 1383.32
   }
  ]
 },
 {
  "paciente": "Guilherme Coelho de Sá",
  "data_cirurgia": "2026-01-23",
  "hospital": "",
  "medico": "",
  "fornecedor": "Max Spine",
  "numero_nf": "123",
  "valor_total": 3072.8,
  "data_nf": "2026-03-05",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-05-10",
    "valor": 1024.26
   },
   {
    "numero": 2,
    "vencimento": "2026-06-10",
    "valor": 1024.26
   },
   {
    "numero": 3,
    "vencimento": "2026-07-10",
    "valor": 1024.26
   }
  ]
 },
 {
  "paciente": "Edson Jose da Silva Lopes",
  "data_cirurgia": "2026-02-26",
  "hospital": "",
  "medico": "Ricardo de Arruda",
  "fornecedor": "RCL Implantes",
  "numero_nf": "413061",
  "valor_total": 15450.0,
  "data_nf": "2026-04-15",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-05-10",
    "valor": 5149.95
   },
   {
    "numero": 2,
    "vencimento": "2026-06-10",
    "valor": 5149.95
   },
   {
    "numero": 3,
    "vencimento": "2026-07-10",
    "valor": 5149.95
   }
  ]
 },
 {
  "paciente": "Reinaldo Almeida",
  "data_cirurgia": "2026-04-07",
  "hospital": "",
  "medico": "Ricardo de Arruda",
  "fornecedor": "Bio2",
  "numero_nf": "87833",
  "valor_total": 14255.0,
  "data_nf": "2026-04-22",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-05-21",
    "valor": 4751.67
   },
   {
    "numero": 2,
    "vencimento": "2026-06-05",
    "valor": 4751.67
   },
   {
    "numero": 3,
    "vencimento": "2026-06-06",
    "valor": 4751.66
   }
  ]
 },
 {
  "paciente": "Eduardo Lino de Jesus",
  "data_cirurgia": "2026-01-26",
  "hospital": "HSE",
  "medico": "Luis Felipe",
  "fornecedor": "Leoni",
  "numero_nf": "17.827",
  "valor_total": 4200.0,
  "data_nf": "2026-02-11",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-05-10",
    "valor": 4200.0
   }
  ]
 },
 {
  "paciente": "Soelene de Castro Farat",
  "data_cirurgia": "2026-03-12",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "4828",
  "valor_total": 16950.0,
  "data_nf": "2026-03-27",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-05-10",
    "valor": 423750.0
   },
   {
    "numero": 2,
    "vencimento": "2026-06-10",
    "valor": 423750.0
   },
   {
    "numero": 3,
    "vencimento": "2026-07-10",
    "valor": 423750.0
   },
   {
    "numero": 4,
    "vencimento": "2026-08-10",
    "valor": 423750.0
   }
  ]
 },
 {
  "paciente": "Jose Brasil da Silva",
  "data_cirurgia": "2026-03-12",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "4829",
  "valor_total": 7500.0,
  "data_nf": "2026-03-27",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-05-10",
    "valor": 3750.0
   },
   {
    "numero": 2,
    "vencimento": "2026-06-10",
    "valor": 3750.0
   }
  ]
 },
 {
  "paciente": "Christhianne Arantes D. B. dos Santos",
  "data_cirurgia": "2026-03-26",
  "hospital": "",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "4953",
  "valor_total": 41505.0,
  "data_nf": "2026-04-29",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 10376.25
   },
   {
    "numero": 2,
    "vencimento": "2026-07-10",
    "valor": 10376.25
   },
   {
    "numero": 3,
    "vencimento": "2026-08-10",
    "valor": 10376.25
   },
   {
    "numero": 4,
    "vencimento": "2026-09-10",
    "valor": 10376.25
   }
  ]
 },
 {
  "paciente": "Jeane Cardoso da Silva",
  "data_cirurgia": "2026-06-09",
  "hospital": "HSE",
  "medico": "Luis Felipe",
  "fornecedor": "Leoni",
  "numero_nf": "17.975",
  "valor_total": 2390.0,
  "data_nf": "2026-03-18",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 2390.0
   }
  ]
 },
 {
  "paciente": "Gabriel de Almeida Leite",
  "data_cirurgia": "2026-02-25",
  "hospital": "",
  "medico": "Luis Felipe",
  "fornecedor": "Leoni",
  "numero_nf": "17923",
  "valor_total": 900.0,
  "data_nf": "2026-03-05",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 900.0
   }
  ]
 },
 {
  "paciente": "Dilson Leal",
  "data_cirurgia": "2026-03-05",
  "hospital": "",
  "medico": "Ederson",
  "fornecedor": "Leoni",
  "numero_nf": "17.968",
  "valor_total": 2180.0,
  "data_nf": "2026-03-17",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 2180.0
   }
  ]
 },
 {
  "paciente": "Maria da Conceição Rodrigues de Amorim",
  "data_cirurgia": "2026-03-18",
  "hospital": "",
  "medico": "Alexandre",
  "fornecedor": "Leoni",
  "numero_nf": "18.009",
  "valor_total": 3900.0,
  "data_nf": "2026-03-24",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 3900.0
   }
  ]
 },
 {
  "paciente": "Joselice Caseiro Gomes Rodrigues",
  "data_cirurgia": "2026-03-11",
  "hospital": "",
  "medico": "Alexandre",
  "fornecedor": "Leoni",
  "numero_nf": "18.025",
  "valor_total": 4500.0,
  "data_nf": "2026-04-15",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 4500.0
   }
  ]
 },
 {
  "paciente": "Manoel Domingos Feitoza",
  "data_cirurgia": "2026-03-28",
  "hospital": "HSE",
  "medico": "Luis Felipe",
  "fornecedor": "Leoni",
  "numero_nf": "18.047",
  "valor_total": 900.0,
  "data_nf": "2026-03-31",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 900.0
   }
  ]
 },
 {
  "paciente": "Charles Soares Paulino Jr.",
  "data_cirurgia": "2026-03-28",
  "hospital": "HSE",
  "medico": "Luis Felipe",
  "fornecedor": "Leoni",
  "numero_nf": "18.046",
  "valor_total": 4200.0,
  "data_nf": "2026-03-31",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 4200.0
   }
  ]
 },
 {
  "paciente": "Zulmira Cordeiro Souza Santos",
  "data_cirurgia": "2026-03-26",
  "hospital": "",
  "medico": "",
  "fornecedor": "Mogami",
  "numero_nf": "72.615",
  "valor_total": 114.0,
  "data_nf": "2026-04-06",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 114.0
   }
  ]
 },
 {
  "paciente": "Zulmira Cordeiro Souza Santos",
  "data_cirurgia": "2026-03-26",
  "hospital": "",
  "medico": "",
  "fornecedor": "Max Spine",
  "numero_nf": "155",
  "valor_total": 8540.5,
  "data_nf": "2026-04-30",
  "total_parcelas": 5,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 1708.1
   },
   {
    "numero": 2,
    "vencimento": "2026-07-10",
    "valor": 1708.1
   },
   {
    "numero": 3,
    "vencimento": "2026-08-10",
    "valor": 1708.1
   },
   {
    "numero": 4,
    "vencimento": "2026-09-10",
    "valor": 1708.1
   },
   {
    "numero": 5,
    "vencimento": "2026-10-10",
    "valor": 1708.1
   }
  ]
 },
 {
  "paciente": "Vanessa Fancelli Zachini",
  "data_cirurgia": "2026-02-19",
  "hospital": "",
  "medico": "",
  "fornecedor": "Isomedical",
  "numero_nf": "95.723",
  "valor_total": 8285.0,
  "data_nf": "2026-04-09",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 4142.5
   },
   {
    "numero": 2,
    "vencimento": "2026-07-10",
    "valor": 4142.5
   }
  ]
 },
 {
  "paciente": "Mario Medeiros",
  "data_cirurgia": "2026-03-19",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "4882",
  "valor_total": 16950.0,
  "data_nf": "2026-04-10",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 4237.5
   },
   {
    "numero": 2,
    "vencimento": "2026-07-10",
    "valor": 4237.5
   },
   {
    "numero": 3,
    "vencimento": "2026-08-10",
    "valor": 4237.5
   },
   {
    "numero": 4,
    "vencimento": "2026-09-10",
    "valor": 4237.5
   }
  ]
 },
 {
  "paciente": "Maikon da Silva Menezes",
  "data_cirurgia": "2026-03-06",
  "hospital": "",
  "medico": "",
  "fornecedor": "Russer",
  "numero_nf": "110.057",
  "valor_total": 3490.0,
  "data_nf": "2026-04-09",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-08",
    "valor": 3490.0
   }
  ]
 },
 {
  "paciente": "Mariana Bispo dos Santos Martins",
  "data_cirurgia": "2026-03-21",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "4881",
  "valor_total": 31170.0,
  "data_nf": "2026-04-10",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 7792.5
   },
   {
    "numero": 2,
    "vencimento": "2026-07-10",
    "valor": 7792.5
   },
   {
    "numero": 3,
    "vencimento": "2026-08-10",
    "valor": 7792.5
   },
   {
    "numero": 4,
    "vencimento": "2026-09-10",
    "valor": 7792.5
   }
  ]
 },
 {
  "paciente": "Mariana Bispo dos Santos Martins",
  "data_cirurgia": "2026-03-21",
  "hospital": "",
  "medico": "",
  "fornecedor": "Schiavão",
  "numero_nf": "9392",
  "valor_total": 6000.0,
  "data_nf": "2026-04-08",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 5631.0
   }
  ]
 },
 {
  "paciente": "Noah Tomadacci Amaral",
  "data_cirurgia": "2026-03-03",
  "hospital": "",
  "medico": "",
  "fornecedor": "Richards Medical",
  "numero_nf": "7167",
  "valor_total": 280.0,
  "data_nf": "2026-04-17",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 140.0
   },
   {
    "numero": 2,
    "vencimento": "2026-07-10",
    "valor": 140.0
   }
  ]
 },
 {
  "paciente": "Thamyres Victoria Quaresma Gonçalves",
  "data_cirurgia": "2026-04-08",
  "hospital": "HSE",
  "medico": "Luis Felipe",
  "fornecedor": "Surgipro",
  "numero_nf": "62",
  "valor_total": 2600.01,
  "data_nf": "2026-04-16",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 1300.01
   },
   {
    "numero": 2,
    "vencimento": "2026-07-10",
    "valor": 1300.0
   }
  ]
 },
 {
  "paciente": "Maria Izabel Machado Fonseca",
  "data_cirurgia": "2026-04-13",
  "hospital": "",
  "medico": "",
  "fornecedor": "Schiavão",
  "numero_nf": "9486",
  "valor_total": 5631.0,
  "data_nf": "2026-04-17",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 2815.5
   },
   {
    "numero": 2,
    "vencimento": "2026-07-10",
    "valor": 2815.5
   }
  ]
 },
 {
  "paciente": "Maria Izabel Machado Fonseca",
  "data_cirurgia": "2026-04-13",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "4962",
  "valor_total": 70920.02,
  "data_nf": "2026-04-29",
  "total_parcelas": 5,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 14184.0
   },
   {
    "numero": 2,
    "vencimento": "2026-07-10",
    "valor": 14184.0
   },
   {
    "numero": 3,
    "vencimento": "2026-08-10",
    "valor": 14184.0
   },
   {
    "numero": 4,
    "vencimento": "2026-09-10",
    "valor": 14184.0
   },
   {
    "numero": 5,
    "vencimento": "2026-10-10",
    "valor": 14184.0
   }
  ]
 },
 {
  "paciente": "Luiz Antonio Cote",
  "data_cirurgia": "2026-02-24",
  "hospital": "HSE",
  "medico": "Carlo Alba",
  "fornecedor": "Itamedical",
  "numero_nf": "9857",
  "valor_total": 4500.0,
  "data_nf": "2026-04-17",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 1500.0
   },
   {
    "numero": 2,
    "vencimento": "2026-07-10",
    "valor": 1500.0
   },
   {
    "numero": 3,
    "vencimento": "2026-08-10",
    "valor": 1500.0
   }
  ]
 },
 {
  "paciente": "Alessandra Aparecida Peixoto Leão",
  "data_cirurgia": "2026-02-24",
  "hospital": "HSE",
  "medico": "Carlo Alba",
  "fornecedor": "Itamedical",
  "numero_nf": "9858",
  "valor_total": 4500.0,
  "data_nf": "2026-04-17",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 1500.0
   },
   {
    "numero": 2,
    "vencimento": "2026-07-10",
    "valor": 1500.0
   },
   {
    "numero": 3,
    "vencimento": "2026-08-10",
    "valor": 1500.0
   }
  ]
 },
 {
  "paciente": "Lucia Cristina dos Santos",
  "data_cirurgia": "2026-04-09",
  "hospital": "HSE",
  "medico": "Andre Luiz D.Costa",
  "fornecedor": "Millenium",
  "numero_nf": "4927",
  "valor_total": 19535.01,
  "data_nf": "2026-04-22",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 4883.75
   },
   {
    "numero": 2,
    "vencimento": "2026-07-10",
    "valor": 4883.75
   },
   {
    "numero": 3,
    "vencimento": "2026-08-10",
    "valor": 4883.75
   },
   {
    "numero": 4,
    "vencimento": "2026-09-10",
    "valor": 4883.75
   }
  ]
 },
 {
  "paciente": "Sandro Fragoso Marcal Silva",
  "data_cirurgia": "2026-04-06",
  "hospital": "HSE",
  "medico": "Carlo Alba",
  "fornecedor": "Itamedical",
  "numero_nf": "9859",
  "valor_total": 4500.0,
  "data_nf": "2026-04-17",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 1500.0
   },
   {
    "numero": 2,
    "vencimento": "2026-07-10",
    "valor": 1500.0
   },
   {
    "numero": 3,
    "vencimento": "2026-08-10",
    "valor": 1500.0
   }
  ]
 },
 {
  "paciente": "Gerson Roberto da Silva",
  "data_cirurgia": "2026-03-30",
  "hospital": "HSE",
  "medico": "Carlo Alba",
  "fornecedor": "Itamedical",
  "numero_nf": "9861",
  "valor_total": 12000.0,
  "data_nf": "2026-04-17",
  "total_parcelas": 5,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 2400.0
   },
   {
    "numero": 2,
    "vencimento": "2026-07-10",
    "valor": 2400.0
   },
   {
    "numero": 3,
    "vencimento": "2026-08-10",
    "valor": 2400.0
   },
   {
    "numero": 4,
    "vencimento": "2026-09-10",
    "valor": 2400.0
   },
   {
    "numero": 5,
    "vencimento": "2026-10-10",
    "valor": 2400.0
   }
  ]
 },
 {
  "paciente": "Gerson Roberto da Silva",
  "data_cirurgia": "2026-03-30",
  "hospital": "HSE",
  "medico": "Carlo Alba",
  "fornecedor": "Itamedical",
  "numero_nf": "9860",
  "valor_total": 295.0,
  "data_nf": "2026-04-17",
  "total_parcelas": 1,
  "situacao": "doacao",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": []
 },
 {
  "paciente": "Silvia Danielle de Souza Caxiado Santos",
  "data_cirurgia": "2026-04-11",
  "hospital": "",
  "medico": "Igor Marijuschkim",
  "fornecedor": "Slim",
  "numero_nf": "35826",
  "valor_total": 960.0,
  "data_nf": "2026-04-22",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 480.0
   },
   {
    "numero": 2,
    "vencimento": "2026-07-10",
    "valor": 480.0
   }
  ]
 },
 {
  "paciente": "Edson Jose Alves",
  "data_cirurgia": "2026-04-09",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "4930",
  "valor_total": 33000.0,
  "data_nf": "2026-04-22",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 8250.0
   },
   {
    "numero": 2,
    "vencimento": "2026-07-10",
    "valor": 8250.0
   },
   {
    "numero": 3,
    "vencimento": "2026-08-10",
    "valor": 8250.0
   },
   {
    "numero": 4,
    "vencimento": "2026-09-10",
    "valor": 8250.0
   }
  ]
 },
 {
  "paciente": "Bruno Guedes Monteiro de Freitas",
  "data_cirurgia": "2026-04-11",
  "hospital": "",
  "medico": "",
  "fornecedor": "Russer",
  "numero_nf": "110.666",
  "valor_total": 2290.0,
  "data_nf": "2026-04-24",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 2290.0
   }
  ]
 },
 {
  "paciente": "Antonio Carlos do Carmo",
  "data_cirurgia": "2026-03-24",
  "hospital": "Santa Casa",
  "medico": "Jefferson dos Santos",
  "fornecedor": "Isomedical",
  "numero_nf": "96762",
  "valor_total": 7626.55,
  "data_nf": "2026-04-27",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 3631.27
   },
   {
    "numero": 2,
    "vencimento": "2026-07-10",
    "valor": 3631.27
   }
  ]
 },
 {
  "paciente": "Vanderlei Gomes Ferreira",
  "data_cirurgia": "2026-03-20",
  "hospital": "Santa Casa",
  "medico": "Jefferson dos Santos",
  "fornecedor": "Isomedical",
  "numero_nf": "96.761",
  "valor_total": 6987.55,
  "data_nf": "2026-04-27",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 3493.77
   },
   {
    "numero": 2,
    "vencimento": "2026-07-10",
    "valor": 3493.77
   }
  ]
 },
 {
  "paciente": "Denise Alves de Mello Lopes",
  "data_cirurgia": "2026-04-17",
  "hospital": "HSE",
  "medico": "Carlo Alba",
  "fornecedor": "Itamedical",
  "numero_nf": "9926",
  "valor_total": 4500.0,
  "data_nf": "2026-04-24",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 1500.0
   },
   {
    "numero": 2,
    "vencimento": "2026-07-10",
    "valor": 1500.0
   },
   {
    "numero": 3,
    "vencimento": "2026-08-10",
    "valor": 1500.0
   }
  ]
 },
 {
  "paciente": "Charles Alves dos Santos",
  "data_cirurgia": "2026-03-20",
  "hospital": "",
  "medico": "Erica Gerard",
  "fornecedor": "Slim Produtos",
  "numero_nf": "35659",
  "valor_total": 3780.0,
  "data_nf": "2026-03-30",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 1260.0
   },
   {
    "numero": 2,
    "vencimento": "2026-07-10",
    "valor": 1260.0
   },
   {
    "numero": 3,
    "vencimento": "2026-08-10",
    "valor": 1260.0
   }
  ]
 },
 {
  "paciente": "Reinaldo Aurelio Pereira",
  "data_cirurgia": "2026-03-19",
  "hospital": "",
  "medico": "",
  "fornecedor": "Isomedical",
  "numero_nf": "96794",
  "valor_total": 1230.0,
  "data_nf": "2026-04-27",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 615.0
   },
   {
    "numero": 2,
    "vencimento": "2026-07-10",
    "valor": 615.0
   }
  ]
 },
 {
  "paciente": "Doraci de Lourdes Ferreira Souza",
  "data_cirurgia": "2026-04-24",
  "hospital": "",
  "medico": "",
  "fornecedor": "Russer",
  "numero_nf": "111.033",
  "valor_total": 3240.0,
  "data_nf": "2026-05-05",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-19",
    "valor": 3240.0
   }
  ]
 },
 {
  "paciente": "Geraldo José de Oliveira",
  "data_cirurgia": "2026-05-06",
  "hospital": "HSE",
  "medico": "Luis Filipe Pellegrini",
  "fornecedor": "Surgipro",
  "numero_nf": "73",
  "valor_total": 2600.01,
  "data_nf": "2026-05-15",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 1000.0
   }
  ]
 },
 {
  "paciente": "Silvio Batista dos Santos",
  "data_cirurgia": "2026-03-24",
  "hospital": "HSE",
  "medico": "Tarcisio Abib Neto",
  "fornecedor": "Handle",
  "numero_nf": "1616385",
  "valor_total": 1480.0,
  "data_nf": "2026-05-26",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-25",
    "valor": 740.0
   },
   {
    "numero": 2,
    "vencimento": "2026-07-25",
    "valor": 740.0
   }
  ]
 },
 {
  "paciente": "Silvio Batista dos Santos",
  "data_cirurgia": "2026-03-24",
  "hospital": "HSE",
  "medico": "Tarcisio Abib Neto",
  "fornecedor": "Handle",
  "numero_nf": "93434",
  "valor_total": 2500.0,
  "data_nf": "2026-05-26",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-25",
    "valor": 1250.0
   },
   {
    "numero": 2,
    "vencimento": "2026-07-25",
    "valor": 1250.0
   }
  ]
 },
 {
  "paciente": "Elizabete Oliveira de Souza",
  "data_cirurgia": "2026-02-06",
  "hospital": "HSE",
  "medico": "Felipe G.da S. Souza",
  "fornecedor": "Max Spine",
  "numero_nf": "166",
  "valor_total": 8540.0,
  "data_nf": "2026-05-15",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 2135.12
   },
   {
    "numero": 2,
    "vencimento": "2026-07-10",
    "valor": 2135.12
   },
   {
    "numero": 3,
    "vencimento": "2026-08-10",
    "valor": 2135.12
   },
   {
    "numero": 4,
    "vencimento": "2026-09-10",
    "valor": 2135.12
   }
  ]
 },
 {
  "paciente": "Annameure de Carvalho Seixa Santos",
  "data_cirurgia": None,
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Surgipro",
  "numero_nf": "68",
  "valor_total": 2000.0,
  "data_nf": "2026-05-12",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 1000.0
   },
   {
    "numero": 1,
    "vencimento": "2026-07-10",
    "valor": 1000.0
   }
  ]
 },
 {
  "paciente": "Maria de Lourdes dos Santos Ramos",
  "data_cirurgia": "2026-04-30",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "5028",
  "valor_total": 35000.01,
  "data_nf": "2026-05-13",
  "total_parcelas": 5,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-06-10",
    "valor": 7000.01
   },
   {
    "numero": 2,
    "vencimento": "2026-07-10",
    "valor": 7000.0
   },
   {
    "numero": 3,
    "vencimento": "2026-08-10",
    "valor": 7000.0
   },
   {
    "numero": 4,
    "vencimento": "2026-09-10",
    "valor": 7000.0
   },
   {
    "numero": 5,
    "vencimento": "2026-10-10",
    "valor": 7000.0
   }
  ]
 },
 {
  "paciente": "Zelia Tavares Gonçalves",
  "data_cirurgia": "2026-04-04",
  "hospital": "",
  "medico": "Luis Felipe",
  "fornecedor": "Leoni",
  "numero_nf": "18.077",
  "valor_total": 6180.0,
  "data_nf": "2026-04-08",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-07-10",
    "valor": 6180.0
   }
  ]
 },
 {
  "paciente": "Matheus Martins Rodrigues",
  "data_cirurgia": "2026-04-13",
  "hospital": "",
  "medico": "Alexandre",
  "fornecedor": "Leoni",
  "numero_nf": "18139",
  "valor_total": 6000.0,
  "data_nf": "2026-04-22",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-07-10",
    "valor": 6000.0
   }
  ]
 },
 {
  "paciente": "Dulcenete Motta de Melo",
  "data_cirurgia": "2026-04-28",
  "hospital": "",
  "medico": "Breno Faria",
  "fornecedor": "Leoni",
  "numero_nf": "18195",
  "valor_total": 8000.0,
  "data_nf": "2026-05-04",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-07-10",
    "valor": 4000.0
   },
   {
    "numero": 2,
    "vencimento": "2026-08-10",
    "valor": 4000.0
   }
  ]
 },
 {
  "paciente": "Kleber Fernandes dos Santos",
  "data_cirurgia": "2026-04-28",
  "hospital": "",
  "medico": "Breno Faria",
  "fornecedor": "Leoni",
  "numero_nf": "18197",
  "valor_total": 900.0,
  "data_nf": "2026-05-04",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-07-10",
    "valor": 900.0
   }
  ]
 },
 {
  "paciente": "Maria Pureza de Jesus Santos",
  "data_cirurgia": "2026-04-28",
  "hospital": "",
  "medico": "Luis Felipe",
  "fornecedor": "Leoni",
  "numero_nf": "18.198",
  "valor_total": 10200.0,
  "data_nf": "2026-05-04",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-07-10",
    "valor": 3400.0
   },
   {
    "numero": 2,
    "vencimento": "2026-08-10",
    "valor": 3400.0
   },
   {
    "numero": 3,
    "vencimento": "2026-09-10",
    "valor": 3400.0
   }
  ]
 },
 {
  "paciente": "Joel Gonçalves dos Santos",
  "data_cirurgia": "2026-05-09",
  "hospital": "HSE",
  "medico": "Luis Felipe",
  "fornecedor": "Surgipro",
  "numero_nf": "77",
  "valor_total": 10500.0,
  "data_nf": "2026-05-29",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-07-10",
    "valor": 2625.0
   },
   {
    "numero": 2,
    "vencimento": "2026-08-10",
    "valor": 2625.0
   },
   {
    "numero": 3,
    "vencimento": "2026-09-10",
    "valor": 2625.0
   },
   {
    "numero": 4,
    "vencimento": "2026-10-10",
    "valor": 2625.0
   }
  ]
 },
 {
  "paciente": "Wilma Alves Dias",
  "data_cirurgia": "2026-04-16",
  "hospital": "HSE",
  "medico": "Andre Luis Domingues",
  "fornecedor": "Millenium",
  "numero_nf": "5042",
  "valor_total": 27862.0,
  "data_nf": "2026-05-15",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-07-10",
    "valor": 6965.5
   },
   {
    "numero": 2,
    "vencimento": "2026-08-10",
    "valor": 6965.5
   },
   {
    "numero": 3,
    "vencimento": "2026-09-10",
    "valor": 6965.5
   },
   {
    "numero": 4,
    "vencimento": "2026-10-10",
    "valor": 6965.5
   }
  ]
 },
 {
  "paciente": "Ednaldo de Pontes Gouveia",
  "data_cirurgia": "2026-05-18",
  "hospital": "HSE",
  "medico": "Erica Gerard",
  "fornecedor": "Leoni",
  "numero_nf": "18314",
  "valor_total": 3990.0,
  "data_nf": "2026-07-10",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-07-10",
    "valor": 2000.0
   },
   {
    "numero": 2,
    "vencimento": "2026-08-10",
    "valor": 1990.0
   }
  ]
 },
 {
  "paciente": "Luiza Silva Pinto de Abreu",
  "data_cirurgia": "2026-05-09",
  "hospital": "HSE",
  "medico": "Luis Filipe Pellegrini",
  "fornecedor": "Surgipro",
  "numero_nf": "75",
  "valor_total": 9500.01,
  "data_nf": "2026-05-25",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-07-10",
    "valor": 3166.67
   },
   {
    "numero": 2,
    "vencimento": "2026-08-10",
    "valor": 3166.67
   },
   {
    "numero": 3,
    "vencimento": "2026-09-10",
    "valor": 3166.67
   }
  ]
 },
 {
  "paciente": "Claudio Luiz dos Santos",
  "data_cirurgia": "2026-05-01",
  "hospital": "",
  "medico": "",
  "fornecedor": "Russer",
  "numero_nf": "111.751",
  "valor_total": 3240.0,
  "data_nf": "2026-05-26",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-07-27",
    "valor": 3240.0
   }
  ]
 },
 {
  "paciente": "Eli Hurtado Ferreira",
  "data_cirurgia": "2026-04-29",
  "hospital": "São Lucas Santos",
  "medico": "Acacio Fernandes",
  "fornecedor": "Micromedical",
  "numero_nf": "73340",
  "valor_total": 32749.0,
  "data_nf": "2026-05-27",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-07-10",
    "valor": 8187.24
   },
   {
    "numero": 2,
    "vencimento": "2026-08-10",
    "valor": 8187.24
   },
   {
    "numero": 3,
    "vencimento": "2026-09-10",
    "valor": 8187.24
   },
   {
    "numero": 4,
    "vencimento": "2026-10-10",
    "valor": 8187.24
   }
  ]
 },
 {
  "paciente": "Eli Hurtado Ferreira",
  "data_cirurgia": "2026-04-29",
  "hospital": "São Lucas Santos",
  "medico": "Acacio Fernandes",
  "fornecedor": "Micromedical",
  "numero_nf": "73341",
  "valor_total": 861.98,
  "data_nf": None,
  "total_parcelas": 1,
  "situacao": "doacao",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": []
 },
 {
  "paciente": "Antonio de Oliveira Paixão",
  "data_cirurgia": "2026-05-04",
  "hospital": "HSE",
  "medico": "Jefferson dos Santos",
  "fornecedor": "Biotronik",
  "numero_nf": "1177349",
  "valor_total": 6000.0,
  "data_nf": "2026-05-26",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-07-10",
    "valor": 1998.98
   },
   {
    "numero": 2,
    "vencimento": "2026-08-10",
    "valor": 1998.98
   },
   {
    "numero": 3,
    "vencimento": "2026-09-10",
    "valor": 2000.04
   }
  ]
 },
 {
  "paciente": "Eleide dos Santos Andrade",
  "data_cirurgia": "2025-05-19",
  "hospital": "HSE",
  "medico": "Rubens Francisco",
  "fornecedor": "Litoral Medic",
  "numero_nf": "6090",
  "valor_total": 12260.0,
  "data_nf": "2026-05-26",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-07-10",
    "valor": 4086.66
   },
   {
    "numero": 2,
    "vencimento": "2026-08-10",
    "valor": 4086.66
   },
   {
    "numero": 3,
    "vencimento": "2026-09-10",
    "valor": 4086.68
   }
  ]
 },
 {
  "paciente": "Ana Maria de Oliveira Souza",
  "data_cirurgia": "2026-04-22",
  "hospital": "HSE",
  "medico": "Luis Filipe Pellegrini",
  "fornecedor": "Surgipro",
  "numero_nf": "74",
  "valor_total": 9000.0,
  "data_nf": "2026-05-25",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-07-10",
    "valor": 2000.0
   },
   {
    "numero": 2,
    "vencimento": "2026-08-10",
    "valor": 2000.0
   },
   {
    "numero": 3,
    "vencimento": "2026-09-10",
    "valor": 2000.0
   },
   {
    "numero": 4,
    "vencimento": "2026-10-10",
    "valor": 2000.0
   }
  ]
 },
 {
  "paciente": "Milene Pontes Belem dos Santos",
  "data_cirurgia": "2026-04-27",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Surgipro",
  "numero_nf": "79",
  "valor_total": 1000.0,
  "data_nf": "2026-06-09",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-07-10",
    "valor": 1000.0
   }
  ]
 },
 {
  "paciente": "Wagner dos Santos Rodrigues",
  "data_cirurgia": "2026-05-22",
  "hospital": "HSE",
  "medico": "",
  "fornecedor": "Mogami",
  "numero_nf": "92.499",
  "valor_total": 4667.6,
  "data_nf": "2026-06-16",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-07-23",
    "valor": 1555.85
   },
   {
    "numero": 2,
    "vencimento": "2026-08-24",
    "valor": 1555.85
   },
   {
    "numero": 3,
    "vencimento": "2026-09-21",
    "valor": 1555.85
   }
  ]
 },
 {
  "paciente": "Rogerio Silvano",
  "data_cirurgia": "2026-06-22",
  "hospital": "HSE",
  "medico": "",
  "fornecedor": "HDL Logistica Hospitalar",
  "numero_nf": "164.079",
  "valor_total": 926.0,
  "data_nf": "2026-06-19",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-07-17",
    "valor": 926.0
   }
  ]
 },
 {
  "paciente": "Silvana Aparecida C. Silva",
  "data_cirurgia": "2026-04-07",
  "hospital": "HSE",
  "medico": "Ricardo de Arruda",
  "fornecedor": "Oteos MB",
  "numero_nf": "42.335",
  "valor_total": 14000.0,
  "data_nf": "2026-06-09",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-07-24",
    "valor": 3500.0
   },
   {
    "numero": 2,
    "vencimento": "2026-08-10",
    "valor": 3500.0
   },
   {
    "numero": 3,
    "vencimento": "2026-09-10",
    "valor": 3500.0
   },
   {
    "numero": 4,
    "vencimento": "2026-10-10",
    "valor": 3500.0
   }
  ]
 },
 {
  "paciente": "Luiz Armando Moraes",
  "data_cirurgia": "2026-05-15",
  "hospital": "HSE",
  "medico": "Jefferson dos Santos",
  "fornecedor": "Biotronik",
  "numero_nf": "1177564",
  "valor_total": 8800.0,
  "data_nf": "2026-05-27",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-07-24",
    "valor": 2933.3
   },
   {
    "numero": 2,
    "vencimento": "2026-08-10",
    "valor": 2933.3
   },
   {
    "numero": 3,
    "vencimento": "2026-09-10",
    "valor": 2933.3
   }
  ]
 },
 {
  "paciente": "Gabriel de Almeida Leite",
  "data_cirurgia": "2026-02-18",
  "hospital": "",
  "medico": "",
  "fornecedor": "Gm Reis",
  "numero_nf": "40.565",
  "valor_total": 12890.0,
  "data_nf": "2026-03-19",
  "total_parcelas": 5,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 5,
    "vencimento": "2026-08-16",
    "valor": 2596.16
   }
  ]
 },
 {
  "paciente": "Luiz Tavares",
  "data_cirurgia": "2026-05-23",
  "hospital": "HSE",
  "medico": "Sergio Ribeiro",
  "fornecedor": "SD Implantes",
  "numero_nf": "6219",
  "valor_total": 29900.0,
  "data_nf": "2026-06-10",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-08-10",
    "valor": 7475.0
   },
   {
    "numero": 2,
    "vencimento": "2026-09-10",
    "valor": 7475.0
   },
   {
    "numero": 3,
    "vencimento": "2026-10-10",
    "valor": 7475.0
   },
   {
    "numero": 4,
    "vencimento": "2026-11-10",
    "valor": 7475.0
   }
  ]
 },
 {
  "paciente": "Ronaldo Lima Teixeira",
  "data_cirurgia": "2026-04-14",
  "hospital": "HSE",
  "medico": "André Luiz Domingues",
  "fornecedor": "Millenium",
  "numero_nf": "5142",
  "valor_total": 18924.0,
  "data_nf": "2026-06-10",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-08-10",
    "valor": 4731.0
   },
   {
    "numero": 2,
    "vencimento": "2026-09-10",
    "valor": 4731.0
   },
   {
    "numero": 3,
    "vencimento": "2026-10-10",
    "valor": 4731.0
   },
   {
    "numero": 4,
    "vencimento": "2026-11-10",
    "valor": 4731.0
   }
  ]
 },
 {
  "paciente": "Alexsander Alves do Santos",
  "data_cirurgia": "2026-05-28",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "5126",
  "valor_total": 16950.0,
  "data_nf": "2026-06-03",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-08-10",
    "valor": 423750.0
   },
   {
    "numero": 2,
    "vencimento": "2026-09-10",
    "valor": 423750.0
   },
   {
    "numero": 3,
    "vencimento": "2026-10-10",
    "valor": 423750.0
   },
   {
    "numero": 4,
    "vencimento": "2026-11-10",
    "valor": 423750.0
   }
  ]
 },
 {
  "paciente": "Carlos Alberto Carvalho Ferreira",
  "data_cirurgia": "2026-05-12",
  "hospital": "HSE",
  "medico": "Alexandre",
  "fornecedor": "Leoni",
  "numero_nf": "18.326",
  "valor_total": 115000.0,
  "data_nf": "2026-05-26",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-08-10",
    "valor": 28750.0
   },
   {
    "numero": 3,
    "vencimento": "2026-09-10",
    "valor": 28750.0
   },
   {
    "numero": 4,
    "vencimento": "2026-10-10",
    "valor": 28750.0
   }
  ]
 },
 {
  "paciente": "Ilda Damasceno Guimarães",
  "data_cirurgia": "2026-05-13",
  "hospital": "HSE",
  "medico": "Carmo",
  "fornecedor": "Leoni",
  "numero_nf": "18327",
  "valor_total": 48000.0,
  "data_nf": "2026-05-26",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-08-10",
    "valor": 16000.0
   },
   {
    "numero": 2,
    "vencimento": "2026-09-10",
    "valor": 16000.0
   },
   {
    "numero": 3,
    "vencimento": "2026-10-10",
    "valor": 16000.0
   }
  ]
 },
 {
  "paciente": "Mario Aparecido Agostinho",
  "data_cirurgia": "2026-06-02",
  "hospital": "HSE",
  "medico": "Breno Faria",
  "fornecedor": "Leoni",
  "numero_nf": "18409",
  "valor_total": 10100.0,
  "data_nf": "2026-06-02",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-08-10",
    "valor": 3367.34
   },
   {
    "numero": 2,
    "vencimento": "2026-09-10",
    "valor": 3366.33
   },
   {
    "numero": 3,
    "vencimento": "2026-10-10",
    "valor": 3366.33
   }
  ]
 },
 {
  "paciente": "Wanderley Sarkis Ligotte",
  "data_cirurgia": "2026-05-27",
  "hospital": "",
  "medico": "Luis Filipe",
  "fornecedor": "Leoni",
  "numero_nf": "18440",
  "valor_total": 21000.0,
  "data_nf": "2026-06-16",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-08-10",
    "valor": 7000.0
   },
   {
    "numero": 2,
    "vencimento": "2026-09-10",
    "valor": 7000.0
   },
   {
    "numero": 3,
    "vencimento": "2026-10-10",
    "valor": 7000.0
   }
  ]
 },
 {
  "paciente": "Mario Otini",
  "data_cirurgia": "2026-05-11",
  "hospital": "",
  "medico": "Luis Filipe",
  "fornecedor": "Leoni",
  "numero_nf": "18441",
  "valor_total": 8600.0,
  "data_nf": "2026-06-16",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-08-10",
    "valor": 4300.0
   }
  ]
 },
 {
  "paciente": "Wilson Osti",
  "data_cirurgia": "2026-05-29",
  "hospital": "",
  "medico": "Luis Filipe",
  "fornecedor": "Leoni",
  "numero_nf": "18442",
  "valor_total": 19500.0,
  "data_nf": "2026-06-16",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-08-10",
    "valor": 6500.0
   },
   {
    "numero": 2,
    "vencimento": "2026-09-10",
    "valor": 6500.0
   },
   {
    "numero": 3,
    "vencimento": "2026-10-10",
    "valor": 6500.0
   }
  ]
 },
 {
  "paciente": "Maria Queiroz de Souza",
  "data_cirurgia": "2026-06-01",
  "hospital": "HSE",
  "medico": "Luis Filipe Pellegrini",
  "fornecedor": "Surgipro",
  "numero_nf": "84",
  "valor_total": 2100.0,
  "data_nf": "2026-06-17",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-08-10",
    "valor": 2100.0
   }
  ]
 },
 {
  "paciente": "Andrea Pekny Carvalho Gomes",
  "data_cirurgia": "2026-06-22",
  "hospital": "HSE",
  "medico": "Anderson Galdo",
  "fornecedor": "Millenium",
  "numero_nf": "5250",
  "valor_total": 2400.0,
  "data_nf": "2026-06-30",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-08-10",
    "valor": 2400.0
   },
   {
    "numero": 1,
    "vencimento": "2026-09-10",
    "valor": 2400.0
   }
  ]
 },
 {
  "paciente": "Maria Hosana Alves da Silva",
  "data_cirurgia": "2026-06-13",
  "hospital": "HSE",
  "medico": "Luis Filipe",
  "fornecedor": "Surgipro",
  "numero_nf": "85",
  "valor_total": 10499.98,
  "data_nf": "2026-06-24",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-08-10",
    "valor": 2624.99
   },
   {
    "numero": 2,
    "vencimento": "2026-09-10",
    "valor": 2625.01
   },
   {
    "numero": 3,
    "vencimento": "2026-10-10",
    "valor": 2624.99
   },
   {
    "numero": 4,
    "vencimento": "2026-11-10",
    "valor": 2624.99
   }
  ]
 },
 {
  "paciente": "Moacir Rodrigues de Souza",
  "data_cirurgia": "2026-07-01",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "5290",
  "valor_total": 16950.0,
  "data_nf": "2026-07-06",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-08-10",
    "valor": 4237.5
   },
   {
    "numero": 2,
    "vencimento": "2026-09-10",
    "valor": 4237.5
   },
   {
    "numero": 3,
    "vencimento": "2026-10-10",
    "valor": 4237.5
   },
   {
    "numero": 4,
    "vencimento": "2026-11-10",
    "valor": 4237.5
   }
  ]
 },
 {
  "paciente": "Valdirene do Valle Quaresma",
  "data_cirurgia": "2026-07-06",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "5289",
  "valor_total": 1998.0,
  "data_nf": "2026-07-06",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-08-10",
    "valor": 999.0
   },
   {
    "numero": 2,
    "vencimento": "2026-09-10",
    "valor": 999.0
   }
  ]
 },
 {
  "paciente": "Maria Pedrosa de Sousa Alcantara Costa",
  "data_cirurgia": "2026-06-22",
  "hospital": "HSE",
  "medico": "Anderson Galdo",
  "fornecedor": "Millenium",
  "numero_nf": "5345",
  "valor_total": 3400.0,
  "data_nf": "2026-07-17",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-08-10",
    "valor": 1700.0
   },
   {
    "numero": 2,
    "vencimento": "2026-09-10",
    "valor": 1700.0
   }
  ]
 },
 {
  "paciente": "Anameure de Carvalho Seixa Santos",
  "data_cirurgia": "2026-07-09",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "5364",
  "valor_total": 28800.01,
  "data_nf": "2026-07-24",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-08-10",
    "valor": 7200.0
   },
   {
    "numero": 2,
    "vencimento": "2026-09-10",
    "valor": 7200.0
   },
   {
    "numero": 3,
    "vencimento": "2026-10-10",
    "valor": 7200.0
   },
   {
    "numero": 4,
    "vencimento": "2026-11-10",
    "valor": 7200.0
   }
  ]
 },
 {
  "paciente": "Mario Otani",
  "data_cirurgia": "2026-05-11",
  "hospital": "",
  "medico": "Luis Filipe",
  "fornecedor": "Leoni",
  "numero_nf": "18441",
  "valor_total": 8600.0,
  "data_nf": "2026-06-16",
  "total_parcelas": 2,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 2,
    "vencimento": "2026-09-10",
    "valor": 4300.0
   }
  ]
 },
 {
  "paciente": "Kelci Rodrigues Ocanha Saraiva",
  "data_cirurgia": "2026-06-23",
  "hospital": "HSE",
  "medico": "Breno Faria",
  "fornecedor": "Leoni",
  "numero_nf": "18504",
  "valor_total": 10100.0,
  "data_nf": "2026-07-02",
  "total_parcelas": 3,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-09-10",
    "valor": 3366.33
   },
   {
    "numero": 2,
    "vencimento": "2026-10-10",
    "valor": 3366.33
   },
   {
    "numero": 3,
    "vencimento": "2026-11-10",
    "valor": 3367.34
   }
  ]
 },
 {
  "paciente": "Lindinaldo Cordeiro de Andrade",
  "data_cirurgia": "2026-06-15",
  "hospital": "HSE",
  "medico": "Welber Meneses",
  "fornecedor": "Millenium",
  "numero_nf": "5212",
  "valor_total": 16950.0,
  "data_nf": "2026-06-25",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-09-10",
    "valor": 4237.5
   },
   {
    "numero": 2,
    "vencimento": "2026-10-10",
    "valor": 4237.5
   },
   {
    "numero": 3,
    "vencimento": "2026-11-10",
    "valor": 4237.5
   },
   {
    "numero": 4,
    "vencimento": "2026-12-10",
    "valor": 4237.5
   }
  ]
 },
 {
  "paciente": "Sonia Maria dos Santos Rodrigues",
  "data_cirurgia": "2026-04-08",
  "hospital": "HSE",
  "medico": "Ricardo Ramirez",
  "fornecedor": "Motiva",
  "numero_nf": "584272",
  "valor_total": 4869.0,
  "data_nf": "2026-05-12",
  "total_parcelas": 4,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-09-10",
    "valor": 1217.25
   },
   {
    "numero": 2,
    "vencimento": "2026-10-10",
    "valor": 1217.25
   },
   {
    "numero": 3,
    "vencimento": "2026-11-10",
    "valor": 1217.25
   },
   {
    "numero": 4,
    "vencimento": "2026-12-10",
    "valor": 1217.25
   }
  ]
 },
 {
  "paciente": "Ruana Santana",
  "data_cirurgia": "2026-04-17",
  "hospital": "HSE",
  "medico": "Monica Mazzurana",
  "fornecedor": "Mogami",
  "numero_nf": "100.268",
  "valor_total": 7460.0,
  "data_nf": "2026-07-10",
  "total_parcelas": 5,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-09-10",
    "valor": 1492.0
   },
   {
    "numero": 2,
    "vencimento": "2026-10-10",
    "valor": 1492.0
   },
   {
    "numero": 3,
    "vencimento": "2026-11-10",
    "valor": 1492.0
   },
   {
    "numero": 4,
    "vencimento": "2026-12-10",
    "valor": 1492.0
   },
   {
    "numero": 5,
    "vencimento": "2027-01-10",
    "valor": 1492.0
   }
  ]
 },
 {
  "paciente": "Caroline Feliciano Baessa",
  "data_cirurgia": "2026-04-17",
  "hospital": "HSE",
  "medico": "Kleber Leandro",
  "fornecedor": "Mogami",
  "numero_nf": "100.267",
  "valor_total": 7460.0,
  "data_nf": "2026-07-10",
  "total_parcelas": 5,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-09-10",
    "valor": 1492.0
   },
   {
    "numero": 2,
    "vencimento": "2026-10-10",
    "valor": 1492.0
   },
   {
    "numero": 3,
    "vencimento": "2026-11-10",
    "valor": 1492.0
   },
   {
    "numero": 4,
    "vencimento": "2026-12-10",
    "valor": 1492.0
   },
   {
    "numero": 5,
    "vencimento": "2027-01-10",
    "valor": 1492.0
   }
  ]
 },
 {
  "paciente": "Solange Maria dos Santos Silva",
  "data_cirurgia": "2026-07-20",
  "hospital": "HSE",
  "medico": "Luis Felipe",
  "fornecedor": "Surgipro",
  "numero_nf": "95",
  "valor_total": 1500.0,
  "data_nf": "2026-07-22",
  "total_parcelas": 1,
  "situacao": "normal",
  "encaminhado": 0,
  "observacao": "",
  "parcelas": [
   {
    "numero": 1,
    "vencimento": "2026-09-10",
    "valor": 1500.0
   }
  ]
 }
]


def sql_txt(valor):
    if valor is None or valor == '':
        return 'NULL'
    return "'" + str(valor).replace('\\', '\\\\').replace("'", "''") + "'"


def sql_data(valor):
    return sql_txt(valor) if valor else 'NULL'


L = []
w = L.append

w('-- APASBS v2 — Carga inicial do OPME')
w('-- GERADO por tools/gerar_opme_dados.py — não edite à mão.')
w('-- Fonte: planilha "Opme 2026 Atual.xlsx".')
w('--')
w('-- Execute DEPOIS de sql/opme.sql:')
w('--   mysql -u usuario -p apasbs < sql/opme_dados.sql')
w('--')
w('-- Reexecutar reescreve as notas que vieram da planilha (mesmo fornecedor,')
w('-- número de NF e paciente) e as parcelas delas. O que for cadastrado pela')
w('-- tela depois disso não é tocado — a não ser que repita essa mesma chave.')
w('')
w('SET NAMES utf8mb4;')
w('')

# ── Fornecedores ────────────────────────────────────────────────────────
fornecedores = sorted({n['fornecedor'] for n in NOTAS})
w('-- ── Fornecedores ────────────────────────────────────────────────────')
w('INSERT INTO opme_fornecedores (nome) VALUES')
w(',\n'.join('    (%s)' % sql_txt(f) for f in fornecedores))
w('ON DUPLICATE KEY UPDATE ativo = 1;')
w('')

# ── Notas ───────────────────────────────────────────────────────────────
w('-- ── Notas fiscais ───────────────────────────────────────────────────')
w('INSERT INTO opme_notas')
w('    (fornecedor_id, numero_nf, paciente, data_cirurgia, hospital, medico,')
w('     valor_total, data_nf, total_parcelas, situacao, encaminhado_hse, observacao)')
w('VALUES')

linhas = []
for n in NOTAS:
    linhas.append(
        "    ((SELECT id FROM opme_fornecedores WHERE nome = %s), %s, %s,\n"
        "     %s, %s, %s, %.2f, %s, %d, '%s', %d, %s)"
        % (sql_txt(n['fornecedor']), sql_txt(n['numero_nf']), sql_txt(n['paciente']),
           sql_data(n['data_cirurgia']), sql_txt(n['hospital']), sql_txt(n['medico']),
           n['valor_total'] or 0, sql_data(n['data_nf']), n['total_parcelas'],
           n['situacao'], n['encaminhado'], sql_txt(n['observacao']))
    )

w(',\n'.join(linhas))
w('ON DUPLICATE KEY UPDATE')
w('    data_cirurgia   = VALUES(data_cirurgia),')
w('    hospital        = VALUES(hospital),')
w('    medico          = VALUES(medico),')
w('    valor_total     = VALUES(valor_total),')
w('    data_nf         = VALUES(data_nf),')
w('    total_parcelas  = VALUES(total_parcelas),')
w('    situacao        = VALUES(situacao),')
w('    encaminhado_hse = VALUES(encaminhado_hse),')
w('    observacao      = VALUES(observacao);')
w('')

# ── Parcelas ────────────────────────────────────────────────────────────
# O vencimento é a chave: no arquivo original o rótulo "3/4" às vezes vinha
# repetido de um mês para o outro, porque a linha era copiada e o número
# esquecido. A data, essa, sempre estava certa — era ela que definia em qual
# aba a linha entrava.
w('-- ── Parcelas ────────────────────────────────────────────────────────')
w('INSERT INTO opme_parcelas (nota_id, numero, vencimento, valor)')
w('VALUES')

linhas = []
for n in NOTAS:
    for p in n['parcelas']:
        linhas.append(
            "    ((SELECT n.id FROM opme_notas n\n"
            "        JOIN opme_fornecedores f ON f.id = n.fornecedor_id\n"
            "       WHERE f.nome = %s AND n.numero_nf = %s AND n.paciente = %s),\n"
            "     %d, %s, %.2f)"
            % (sql_txt(n['fornecedor']), sql_txt(n['numero_nf']), sql_txt(n['paciente']),
               p['numero'], sql_txt(p['vencimento']), p['valor'] or 0)
        )

w(',\n'.join(linhas))
w('ON DUPLICATE KEY UPDATE numero = VALUES(numero), valor = VALUES(valor);')
w('')

w('SELECT')
w('    (SELECT COUNT(*) FROM opme_fornecedores) AS fornecedores,')
w('    (SELECT COUNT(*) FROM opme_notas)        AS notas,')
w('    (SELECT COUNT(*) FROM opme_parcelas)     AS parcelas,')
w("    (SELECT FORMAT(SUM(valor), 2) FROM opme_parcelas) AS soma_parcelas;")

print('\n'.join(L))

<?php

return [

    








    'ambientes' => [
        1 => [
            'nombre' => 'PRUEBAS',
            'recepcion' => 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl',
            'autorizacion' => 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl',
            'consulta' => 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/ConsultaComprobante?wsdl',
        ],
        2 => [
            'nombre' => 'PRODUCCION',
            'recepcion' => 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl',
            'autorizacion' => 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl',
            'consulta' => 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/ConsultaComprobante?wsdl',
        ],
    ],

    










    'autorizacion_delay_inicial' => env('SRI_AUTORIZACION_DELAY_INICIAL', 90),
    'autorizacion_delay_reintento' => env('SRI_AUTORIZACION_DELAY_REINTENTO', 60),
    'autorizacion_max_horas' => env('SRI_AUTORIZACION_MAX_HORAS', 24),

    





    'consulta_anulacion_intervalo_minutos' => env('SRI_CONSULTA_ANULACION_INTERVALO_MINUTOS', 60),

    










    'identificacion_consumidor_final' => '9999999999999',

    





    'soap_timeout' => env('SRI_SOAP_TIMEOUT', 30),

];

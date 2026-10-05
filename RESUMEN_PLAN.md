# Resumen de ejecución de PLAN.md

Pasos evaluados en orden. Se ejecutó `php artisan test` al terminar cada paso, incluidos los saltados. Ninguna corrida de pruebas falló. Resultado final: **99 pruebas, 324 aserciones, todas aprobadas**.

Sin dependencias nuevas; sin modificar vendor/, node_modules/, .env ni certificados existentes. Sin acciones manuales, llamadas SOAP ni comandos sobre la BD real. Almacenamiento de pruebas y certificados ficticios aislados en `/private/tmp/sri-plan-tests`; BD SQLite `:memory:`. No hay repositorio .git en esta copia; la referencia inicial para comparar está en `/private/tmp/sri-plan-baseline`.

## Paso 1

Cola sri habilitada en Horizon y en desarrollo.

`php artisan test`: **81 aprobadas, 240 aserciones**. Registro: `/private/tmp/sri-plan-step-1.log`.

Archivos cambiados:

- [config/horizon.php](config/horizon.php)

- [composer.json](composer.json)



## Paso 2

NO AUTORIZADO y RECHAZADO terminan en rechazada; errores de consulta reintentan hasta el plazo; fallo final de recepción deja advertencia sin sobrescribir estados resueltos.

`php artisan test`: **83 aprobadas, 248 aserciones**. Registro: `/private/tmp/sri-plan-step-2.log`.

Archivos cambiados:

- [app/Jobs/ConsultarAutorizacionJob.php](app/Jobs/ConsultarAutorizacionJob.php)

- [app/Jobs/EnviarComprobanteRecepcionJob.php](app/Jobs/EnviarComprobanteRecepcionJob.php)

- [tests/Feature/Jobs/ConsultarAutorizacionJobTest.php](tests/Feature/Jobs/ConsultarAutorizacionJobTest.php)

- [tests/Feature/Jobs/EnviarComprobanteRecepcionJobTest.php](tests/Feature/Jobs/EnviarComprobanteRecepcionJobTest.php)



## Paso 3

Bloqueo y comprobación del estado dentro de la transacción, mensaje amigable y error 43 dirigido a consulta de autorización. Prueba con modelo desactualizado y un solo envío/secuencial.

`php artisan test`: **85 aprobadas, 255 aserciones**. Registro: `/private/tmp/sri-plan-step-3.log`.

Archivos cambiados:

- [app/Actions/Sri/ConfirmarFacturaAction.php](app/Actions/Sri/ConfirmarFacturaAction.php)

- [app/Livewire/Facturas/FacturaIndex.php](app/Livewire/Facturas/FacturaIndex.php)

- [app/Jobs/EnviarComprobanteRecepcionJob.php](app/Jobs/EnviarComprobanteRecepcionJob.php)

- [tests/Feature/Sri/ConfirmarFacturaActionTest.php](tests/Feature/Sri/ConfirmarFacturaActionTest.php)

- [tests/Feature/Jobs/EnviarComprobanteRecepcionJobTest.php](tests/Feature/Jobs/EnviarComprobanteRecepcionJobTest.php)



## Paso 4

Redondeo por línea, IVA, descuentos, totales y agrupación XML; pruebas de dos líneas de 1.005 y coherencia entre encabezado y detalles.

`php artisan test`: **87 aprobadas, 262 aserciones**. Registro: `/private/tmp/sri-plan-step-4.log`.

Archivos cambiados:

- [app/Actions/Facturas/GuardarBorradorFacturaAction.php](app/Actions/Facturas/GuardarBorradorFacturaAction.php)

- [app/Services/Sri/FacturaXmlBuilder.php](app/Services/Sri/FacturaXmlBuilder.php)

- [tests/Unit/GuardarBorradorFacturaActionTest.php](tests/Unit/GuardarBorradorFacturaActionTest.php)

- [tests/Unit/FacturaXmlBuilderTest.php](tests/Unit/FacturaXmlBuilderTest.php)



## Paso 5

Mapa IVA 0/5/8/15, validación de catálogos, subsidio, descuento, identificación y límite de consumidor final. Vista previa tolera una tarifa inválida mientras la validación impide guardarla.

`php artisan test`: **90 aprobadas, 290 aserciones**. Registro: `/private/tmp/sri-plan-step-5.log`.

Archivos cambiados:

- [app/Actions/Facturas/GuardarBorradorFacturaAction.php](app/Actions/Facturas/GuardarBorradorFacturaAction.php)

- [app/Livewire/Facturas/FacturaForm.php](app/Livewire/Facturas/FacturaForm.php)

- [tests/Unit/GuardarBorradorFacturaActionTest.php](tests/Unit/GuardarBorradorFacturaActionTest.php)

- [tests/Feature/Livewire/FacturaFormTest.php](tests/Feature/Livewire/FacturaFormTest.php)



## Paso 6

SALTADO por riesgo: devuelta puede resultar de un timeout con recepción efectiva. El plan no distingue ese caso antes de permitir modificar y firmar contenido con la misma clave, ni define qué ocurre al cambiar la fecha de emisión que integra la clave. No se habilitó edición ni reenvío.

`php artisan test`: **90 aprobadas, 290 aserciones**. Registro: `/private/tmp/sri-plan-step-6.log`.

Archivos cambiados: ninguno en este paso.



## Paso 7

America/Guayaquil como zona predeterminada, ejemplo de configuración y prueba de fecha nocturna. La línea de .env.example se corrigió en la revisión final y se volvió a ejecutar la suite completa. .env no se modificó.

`php artisan test`: **91 aprobadas, 292 aserciones**. Registro: `/private/tmp/sri-plan-step-7.log`.

Archivos cambiados:

- [config/app.php](config/app.php)

- [.env.example](.env.example)

- [tests/Unit/FacturaFechaEmisionTest.php](tests/Unit/FacturaFechaEmisionTest.php)



## Paso 8

Fin de día en ambos límites y pruebas del día 7 a las 18:00, día siguiente y vencimiento de nota de crédito.

`php artisan test`: **92 aprobadas, 296 aserciones**. Registro: `/private/tmp/sri-plan-step-8.log`.

Archivos cambiados:

- [app/Services/Sri/AnulacionElegibilidadService.php](app/Services/Sri/AnulacionElegibilidadService.php)

- [tests/Unit/AnulacionElegibilidadServiceTest.php](tests/Unit/AnulacionElegibilidadServiceTest.php)



## Paso 9

SALTADO por incompatibilidad: el plan combina seis decimales con validación XML v1.0.0. La ficha oficial introduce seis decimales en v1.1.0 (anexo 3). No se cambió la versión del XML ni se amplió la precisión ni se implementaron parcialmente sus campos adicionales. Persisten esas limitaciones.

`php artisan test`: **92 aprobadas, 296 aserciones**. Registro: `/private/tmp/sri-plan-step-9.log`.

Archivos cambiados: ninguno en este paso.



## Paso 10

RUC único, datos fiscales protegidos para admin, validación PKCS#12 previa a escritura y archivo nuevo con nombre único; se conserva el anterior ante fallos. Ambiente persistido por factura y pasado a los tres clientes SOAP. Para facturas anteriores se obtiene de la clave; si no es determinable se falla sin consultar un ambiente supuesto. Migración aditiva, ejecutada solo en SQLite de pruebas; down conserva la columna. EmpresaPolicy no requirió cambios porque ya autoriza el acceso y la restricción de campos está en el formulario.

`php artisan test`: **98 aprobadas, 322 aserciones**. Registro: `/private/tmp/sri-plan-step-10.log`.

Archivos cambiados:

- [app/Livewire/Empresas/EmpresaForm.php](app/Livewire/Empresas/EmpresaForm.php)

- [app/Actions/Empresas/GuardarEmpresaAction.php](app/Actions/Empresas/GuardarEmpresaAction.php)

- [app/Models/Factura.php](app/Models/Factura.php)

- [app/Actions/Sri/ConfirmarFacturaAction.php](app/Actions/Sri/ConfirmarFacturaAction.php)

- [app/Services/Sri/ClaveAccesoService.php](app/Services/Sri/ClaveAccesoService.php)

- [app/Services/Sri/FacturaXmlBuilder.php](app/Services/Sri/FacturaXmlBuilder.php)

- [app/Services/Sri/SriRecepcionClient.php](app/Services/Sri/SriRecepcionClient.php)

- [app/Services/Sri/SriAutorizacionClient.php](app/Services/Sri/SriAutorizacionClient.php)

- [app/Services/Sri/SriConsultaComprobanteClient.php](app/Services/Sri/SriConsultaComprobanteClient.php)

- [app/Jobs/EnviarComprobanteRecepcionJob.php](app/Jobs/EnviarComprobanteRecepcionJob.php)

- [app/Jobs/ConsultarAutorizacionJob.php](app/Jobs/ConsultarAutorizacionJob.php)

- [app/Jobs/ConsultarEstadoAnulacionJob.php](app/Jobs/ConsultarEstadoAnulacionJob.php)

- [database/migrations/2026_10_03_000000_add_ambiente_to_facturas_table.php](database/migrations/2026_10_03_000000_add_ambiente_to_facturas_table.php)

- [tests/Feature/Livewire/EmpresaFormTest.php](tests/Feature/Livewire/EmpresaFormTest.php)

- [tests/Feature/Sri/ConfirmarFacturaActionTest.php](tests/Feature/Sri/ConfirmarFacturaActionTest.php)

- [tests/Feature/Jobs/EnviarComprobanteRecepcionJobTest.php](tests/Feature/Jobs/EnviarComprobanteRecepcionJobTest.php)

- [tests/Feature/Jobs/ConsultarAutorizacionJobTest.php](tests/Feature/Jobs/ConsultarAutorizacionJobTest.php)

- [tests/Feature/Jobs/ConsultarEstadoAnulacionJobTest.php](tests/Feature/Jobs/ConsultarEstadoAnulacionJobTest.php)

- [tests/Feature/Jobs/FacturaAmbienteTest.php](tests/Feature/Jobs/FacturaAmbienteTest.php)



## Paso 11

SALTADO por ambigüedad: el plan remite expresamente la decisión del comportamiento de anulación a negocio en Acciones manuales. No define calendario de días hábiles ni tratamiento de solicitudes históricas sin fecha. No se modificó el barrido ni se creó la columna de solicitud.

`php artisan test`: **98 aprobadas, 322 aserciones**. Registro: `/private/tmp/sri-plan-step-11.log`.

Archivos cambiados: ninguno en este paso.



## Paso 12

Búsqueda literal de comodines con parámetros SQL y ESCAPE explícito compatible con SQLite/PostgreSQL; consumidor final centralizado en config, incluyendo factory y prueba existentes; README de 15 líneas.

`php artisan test`: **99 aprobadas, 324 aserciones**. Registro: `/private/tmp/sri-plan-step-12.log`.

Archivos cambiados:

- [app/Livewire/Facturas/FacturaForm.php](app/Livewire/Facturas/FacturaForm.php)

- [app/Models/Factura.php](app/Models/Factura.php)

- [database/factories/FacturaFactory.php](database/factories/FacturaFactory.php)

- [tests/Feature/Livewire/FacturaFormBuscadorProductosTest.php](tests/Feature/Livewire/FacturaFormBuscadorProductosTest.php)

- [tests/Unit/AnulacionElegibilidadServiceTest.php](tests/Unit/AnulacionElegibilidadServiceTest.php)

- [README.md](README.md)



## Verificación y limitaciones

La configuración de Horizon muestra `default` y `sri`; el script dev incluye `--queue=sri,default`. La búsqueda `rg IDENTIFICACION_CONSUMIDOR_FINAL app` no devuelve resultados. README tiene 15 líneas. La prueba de zona horaria confirma America/Guayaquil sin ejecutar Tinker.

La suite prueba idempotencia con un modelo desactualizado sobre SQLite; no demuestra concurrencia real de bloqueos en PostgreSQL. No se validó el XML contra XSD por haberse saltado el paso 9. No se ejecutó ninguna migración sobre una base persistente. La migración nueva no elimina la columna en down para conservar el ambiente histórico.

Se consultó únicamente documentación pública: [ficha técnica oficial v2.34, tabla 17 y anexo 3](https://www.sri.gob.ec/o/sri-portlet-biblioteca-alfresco-internet/descargar/f8d9bb36-5632-4f96-b463-b9265b55338c/FICHA%20TE%CC%81CNICA%20COMPROBANTES%20ELECTRO%CC%81NICOS%20ESQUEMA%20OFFLINE%20Versio%CC%81n%202.34.pdf). No se probaron envíos contra celcer ni producción.

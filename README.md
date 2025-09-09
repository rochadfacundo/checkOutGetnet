# Módulo de Pago checkOut

Este módulo se encarga de integrar el checkout de la pasarela de pagos **Getnet** para procesar pagos online.  
Su función principal es:

- Generar la orden de pago en Getnet.
- Redirigir al usuario al checkout seguro.
- Recibir las notificaciones (webhooks) de confirmación.
- Guardar el resultado de cada transacción en la base de datos.  

De esta forma, el sistema puede registrar y verificar si un pago fue aprobado, rechazado o está pendiente.

---

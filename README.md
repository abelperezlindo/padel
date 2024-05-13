## Configuración:
- Instalar este módulo.
- Editar el campo “field_padel_courts” tipo de contenido “Padel court reservation” creado por este mismo módulo. En este campo de tipo referencia a entidad, cambiar el termino de taxonomía al cual se hace referencia (por defecto es tags) por el término que representa las pistas de padel.
Ir a /admin/structure/types/manage/padel_court_reservation/fields/node.padel_court_reservation.field_padel_courts
- Ir al formulario de configuración del modulo en /admin/config/pistas-padel/settings
- El selector Padel court debe tener el nombre del campo el cual hace referencia a las pistas de padel.
- “Tranche duration” indica la duración de cada “porción reservable” por decirlo de alguna forma.
- Los campos “Text to display …” permiten establecer los mensajes a mostar a los usuarios cuando intenten hacer una reserva.
- En la seccion Availability puede configurar que días de la semana y en que horarios se pueden realizar reservas.
- Para poder realizar una reserva, un usuario debe tener el permiso “permission to reserve paddle tennis courts”.
- Para poder Bloquear una reserva, un usuario debe tener el permiso “permission to block reservation”. Al bloquear una reserva esta ya no estará disponible.
- Debe posicionar el bloque con el calendario “Pistas Padel calendar block” en el lugar donde quieras mostrarlo. También está disponible la ruta /admin/config/pistas-padel/calendar
- El usuario que desea reservar debe ingresar una fecha (por defecto es la actual) y hacer click en el o los cuadrados que quiera reservar, si la pista esta disponible en el orario seleccionado podrá confirmar la reserva, caso contrario solo se le mostrará un mensaje indicándole que la pista no está disponible.
<?php
// src/views/calificaciones_trabajador.php
// ---------------------------------------------------------------------------
// Fase 8b (cambio 3.3): vista de "Mis calificaciones" como FRAGMENTO que se
// incluye dentro de public/mi_perfil.php (antes era la página aparte
// mis_calificaciones.php, que ya no está en el menú).
//   CU-12  resumen: promedio, cantidad de calificaciones y trabajos completados
//   CU-20  responder / editar la respuesta pública a una reseña
//   CU-14  reportar una reseña inapropiada (una sola vez)
// Recibe la variable $panelCal (CalificacionController::panelDelTrabajador).
// Los formularios envían a mi_perfil.php (accion = responder | reportar).
// ---------------------------------------------------------------------------
?>
    <!-- ============ RESUMEN (CU-12 paso 2) ============ -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm h-100"><div class="card-body text-center">
                <div class="fs-3"><?= Vista::estrellas($panelCal['promedio']) ?></div>
                <div class="small text-muted">Puntaje promedio</div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100"><div class="card-body text-center">
                <div class="display-6"><?= $panelCal['cantidad'] ?></div>
                <div class="small text-muted">Calificaciones recibidas</div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm h-100"><div class="card-body text-center">
                <div class="display-6"><?= $panelCal['completados'] ?></div>
                <div class="small text-muted">Trabajos completados</div>
            </div></div>
        </div>
    </div>

    <!-- ============ LISTA DE RESEÑAS ============ -->
    <?php if (empty($panelCal['resenias'])): ?>
        <!-- CU-12 excepción 2.1 (los trabajos completados se muestran igual, arriba) -->
        <div class="alert alert-info">Aún no recibiste calificaciones.</div>
    <?php else: ?>
        <?php foreach ($panelCal['resenias'] as $r): ?>
            <?php $id = (int) $r['id_calificacion']; $tieneRespuesta = !empty($r['respuesta']); ?>
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between flex-wrap">
                        <strong><?= Vista::e($r['nombre_cliente'] . ' ' . $r['apellido_cliente']) ?></strong>
                        <span class="small text-muted"><?= Vista::e(Vista::fecha($r['fecha'])) ?></span>
                    </div>
                    <div><?= Vista::estrellas($r['puntuacion']) ?></div>

                    <?php if (!empty($r['comentario'])): ?>
                        <p class="mb-2 mt-1" style="white-space: pre-wrap;"><?= Vista::e($r['comentario']) ?></p>
                    <?php else: ?>
                        <p class="text-muted small mb-2 mt-1">Sin comentario.</p>
                    <?php endif; ?>

                    <?php if ($tieneRespuesta): ?>
                        <div class="bg-light border-start border-3 ps-3 py-2 small mb-2">
                            <strong>Tu respuesta:</strong><br>
                            <span style="white-space: pre-wrap;"><?= Vista::e($r['respuesta']) ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- CU-20 paso 2 y excepción 3.1: "Responder" o, si ya hay respuesta, "Editar respuesta" -->
                    <button class="btn btn-outline-primary btn-sm" type="button"
                            data-bs-toggle="collapse" data-bs-target="#resp-<?= $id ?>">
                        <?= $tieneRespuesta ? 'Editar respuesta' : 'Responder' ?>
                    </button>

                    <!-- Fase 7 - CU-14: reportar. Solo una vez por reseña. -->
                    <?php if ($r['reportada']): ?>
                        <span class="badge text-bg-warning ms-1">Reportada · en revisión</span>
                    <?php elseif ($r['motivo_reporte'] !== null): ?>
                        <span class="badge text-bg-secondary ms-1">Revisada por el administrador: se mantiene</span>
                    <?php else: ?>
                        <button class="btn btn-outline-danger btn-sm ms-1" type="button"
                                data-bs-toggle="collapse" data-bs-target="#rep-<?= $id ?>">Reportar</button>
                    <?php endif; ?>

                    <?php if ($r['motivo_reporte'] === null): ?>
                        <div class="collapse mt-3" id="rep-<?= $id ?>">
                            <form method="post" onsubmit="return confirm('¿Reportar esta calificación al administrador?');">
                                <?= Vista::campoCsrf() ?>
                                <input type="hidden" name="accion" value="reportar">
                                <input type="hidden" name="id_calificacion" value="<?= $id ?>">
                                <input type="text" name="motivo" class="form-control mb-2" maxlength="255" required
                                       placeholder="¿Por qué la reportás? (ej: lenguaje ofensivo)">
                                <button type="submit" class="btn btn-danger btn-sm">Enviar reporte</button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <div class="collapse mt-3" id="resp-<?= $id ?>">
                        <form method="post">
                            <?= Vista::campoCsrf() ?>
                            <input type="hidden" name="accion" value="responder">
                            <input type="hidden" name="id_calificacion" value="<?= $id ?>">
                            <textarea name="contenido" class="form-control mb-2" rows="3" maxlength="1000"
                                      placeholder="Escribí tu respuesta pública" required><?= Vista::e($r['respuesta'] ?? '') ?></textarea>
                            <button type="submit" class="btn btn-primary btn-sm">Publicar respuesta</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

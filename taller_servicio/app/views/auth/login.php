<?php

declare(strict_types=1);

$title = $title ?? 'Ingresar al sistema';
$errors = $errors ?? [];
$old = $old ?? [];

ob_start();
?>
<div class="row justify-content-center">
    <div class="col-lg-5 col-md-7">
        <div class="card shadow-sm border-0">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <i class="fa-solid fa-shield-halved fa-3x text-primary mb-3"></i>
                    <h1 class="h4 fw-bold">Acceso al Sistema</h1>
                    <p class="text-muted">Ingresa tus credenciales para continuar.</p>
                </div>

                <?php if (!empty($errors['general'])): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($errors['general'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>

                <form action="/login" method="POST" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="email" class="form-label">Correo electrónico</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                            <input
                                type="email"
                                class="form-control <?= isset($errors['email']) ? 'is-invalid' : ''; ?>"
                                id="email"
                                name="email"
                                value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                                required
                                autocomplete="username"
                                placeholder="usuario@dominio.com"
                            >
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['email'] ?? 'Ingresa un correo válido.', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                            <input
                                type="password"
                                class="form-control <?= isset($errors['password']) ? 'is-invalid' : ''; ?>"
                                id="password"
                                name="password"
                                required
                                minlength="8"
                                autocomplete="current-password"
                            >
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword" aria-label="Mostrar u ocultar contraseña">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['password'] ?? 'La contraseña es obligatoria.', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <a href="#" class="small" id="recoveryLink"><i class="fa-solid fa-key me-1"></i>¿Olvidaste tu contraseña?</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-right-to-bracket me-2"></i>Ingresar
                        </button>
                    </div>
                </form>

                <div class="text-center small text-muted">
                    <p class="mb-1"><strong>Reglas de seguridad</strong></p>
                    <ul class="list-unstyled mb-0">
                        <li><i class="fa-solid fa-circle-check text-success me-1"></i>Se bloquea tras múltiples intentos fallidos.</li>
                        <li><i class="fa-solid fa-circle-check text-success me-1"></i>Contraseña protegida con hash seguro.</li>
                        <li><i class="fa-solid fa-circle-check text-success me-1"></i>Auditoría completa de accesos.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

ob_start();
?>
<script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        const passwordInput = document.getElementById('password');
        const icon = this.querySelector('i');
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    });

    document.getElementById('recoveryLink').addEventListener('click', function (event) {
        event.preventDefault();
        Swal.fire({
            icon: 'info',
            title: 'Recuperar contraseña',
            html: '<p class="mb-3">Ingresa tu correo para enviarte un enlace seguro de restablecimiento.</p>' +
                '<input type="email" id="recoveryEmail" class="swal2-input" placeholder="correo@dominio.com" required>',
            confirmButtonText: 'Enviar enlace',
            showCancelButton: true,
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                const email = (document.getElementById('recoveryEmail') || {}).value || '';
                if (!email || !/^\\S+@\\S+\\.\\S+$/.test(email)) {
                    Swal.showValidationMessage('Ingresa un correo electrónico válido.');
                }
                return { email };
            }
        }).then(result => {
            if (result.isConfirmed) {
                Swal.fire({
                    icon: 'success',
                    title: 'Solicitud recibida',
                    text: 'Si el correo existe, enviaremos un enlace para restablecer la contraseña.',
                });
            }
        });
    });
</script>
<?php
$scripts = ob_get_clean();

require __DIR__ . '/../layouts/main.php';

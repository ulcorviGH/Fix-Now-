# Dockerfile
# ---------------------------------------------------------------------------
# Receta para construir el contenedor que va a correr FixNow.
# Partimos de una imagen oficial que YA trae PHP 8.2 y Apache instalados
# y le agregamos justo lo que a FixNow le hace falta.
# ---------------------------------------------------------------------------

FROM php:8.2-apache

# 1) Instalamos la extensión pdo_mysql: es la que le permite a PHP
#    hablar con MySQL usando PDO (Consultas Preparadas), tal como se
#    definió en la arquitectura de software del proyecto.
RUN docker-php-ext-install pdo pdo_mysql mysqli

# 2) Habilitamos mod_rewrite de Apache: lo vamos a necesitar más adelante
#    para tener URLs más prolijas (ej. /perfil/12 en vez de /perfil.php?id=12).
RUN a2enmod rewrite

# 3) Le decimos a Apache que la carpeta pública del sitio (la única
#    que el navegador puede ver) es "public/", NO la raíz del proyecto.
#    Esto es clave para que src/ (nuestro código PHP interno) quede
#    inaccesible desde internet.
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 4) Damos permisos de escritura a la carpeta de imágenes, porque ahí
#    vamos a guardar las fotos de perfil y de portfolio que suban
#    los trabajadores (CU-09, CU-17).
RUN mkdir -p /var/www/html/public/assets/img/perfiles /var/www/html/public/assets/img/portfolio

# 5) Fase 7: copiamos los ajustes de PHP para subir fotos de hasta 5 MB.
#    (Como cambió el Dockerfile, hay que correr UNA vez: docker compose up -d --build)
COPY uploads.ini /usr/local/etc/php/conf.d/uploads.ini

# 6) Fase 8: seguridad. Copiamos (a) los ajustes de PHP de producción (errores
#    fuera de pantalla, cookies de sesión seguras) y (b) los ajustes de Apache
#    (sin versión visible, sin listado de carpetas, sin ejecutar PHP en la
#    carpeta de fotos) y activamos el de Apache con a2enconf.
#    (Como cambió el Dockerfile, hay que correr UNA vez: docker compose up -d --build)
COPY seguridad.ini /usr/local/etc/php/conf.d/seguridad.ini
COPY zz-fixnow-seguridad.conf /etc/apache2/conf-available/zz-fixnow-seguridad.conf
RUN a2enconf zz-fixnow-seguridad

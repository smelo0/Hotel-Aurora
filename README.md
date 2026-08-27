## Instalación (php 8.3.3)

1. Clona el repositorio:
   git clone https://github.com/usuario/repositorio.git

2. Instala las dependencias:

   # O para PHP
    Composer install


# 🐙 Guía de Trabajo con Git y GitHub

Instrucciones esenciales para gestionar tu proyecto, sincronizar código y trabajar con ramas en GitHub.

---

## 🚀 1. Configuración Inicial e Inicialización

# Verificar que la vinculación se realizó correctamente
git remote -v

Nota: Si vas a trabajar sobre un proyecto que ya existe en GitHub, simplemente clónalo:

Bash
git clone https://github.com/smelo0/Hotel-Aurora

  
## 🔄 2. Flujo Básico de Cambios

# 1. Ver el estado de tus archivos (modificados, agregados o eliminados)
git status

# 2. Preparar todos los archivos modificados
git add .

# 3. Guardar los cambios en el historial local con un mensaje
git commit -m "Mensaje descriptivo de los cambios"

# 4. Traer cambios recientes del servidor para evitar conflictos
git pull origin main

# 5. Subir tus cambios a GitHub
git push origin main

  
## 🌿 3. Trabajo con Ramas (Branches)
Para desarrollar nuevas funcionalidades o corregir errores sin afectar el código principal:

# Crear y cambiar a una nueva rama
git checkout -b feature/nueva-funcionalidad

# (Realizas tus cambios, haces git add . y git commit)

# Subir la nueva rama a GitHub por primera vez
git push -u origin feature/nueva-funcionalidad

# Ver en qué rama te encuentras actualmente
git branch


## 🔀 4. Fusionar y Limpiar Ramas
Una vez terminada tu tarea en la rama secundaria:

# 1. Cambiar a la rama principal
git checkout main

# 2. Descargar la última versión de main
git pull origin main

# 3. Fusionar los cambios de tu rama a main
git merge feature/nueva-funcionalidad

# 4. Subir la rama main actualizada a GitHub
git push origin main

# 5. Borrar la rama local (opcional)
git branch -d feature/nueva-funcionalidad

# 6. Borrar la rama en GitHub (opcional)
git push origin --delete feature/nueva-funcionalidad

  
## 🗑️ 5. Eliminación de Archivos
Si necesitas borrar un archivo del proyecto y de GitHub:

# Eliminar el archivo del sistema local y de Git
git rm nombre-del-archivo.ext

# Guardar la eliminación
git commit -m "Delete: nombre-del-archivo.ext"

# Impactar el borrado en GitHub
git push origin main

  
#💡 Tip: Si solo quieres borrar un archivo de GitHub pero conservarlo en tu equipo local, usa:
git rm --cached nombre-del-archivo.ext

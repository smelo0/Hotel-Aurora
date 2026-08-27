1. Crear y cambiar de rama

Bash
# Crear una nueva rama y moverte a ella
git checkout -b nombre-de-la-rama

# O la sintaxis más moderna:
git switch -c nombre-de-la-rama


2. Guardar cambios en tu rama

Bash
# Preparar los archivos modificados
git add .

# Guardar los cambios con un mensaje
git commit -m "Descripción de lo que hiciste"


3. Subir la rama a GitHub
Bash
# Subir la rama por primera vez y vincularla
git push -u origin nombre-de-la-rama

# Para posteriores subidas dentro de la misma rama
git push


4. Fusionar los cambios a la rama principal (main)

Bash
# 1. Cambiar a la rama principal
git checkout main

# 2. Traer la versión más reciente del servidor
git pull origin main

# 3. Traer los cambios de tu rama a main
git merge nombre-de-la-rama

# 4. Subir la rama principal actualizada
git push origin main


5. Limpieza de ramas

Bash
# Borrar la rama localmente cuando ya no la necesites
git branch -d nombre-de-la-rama

# Borrar la rama en el servidor (GitHub)
git push origin --delete nombre-de-la-rama
Comando útil de consulta:

git branch — Muestra la lista de ramas locales y resalta en la que estás actualmente.

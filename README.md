# php-todo-k3s

Minimal PHP Todo app for the GitHub → ECR → K3s pipeline demo.
SQLite storage, single container, tiny footprint — fits a small EC2.

See the tutorial document for the full step-by-step. Files:

- `app/index.php`    the app (todo list, /?action=health, /?action=version)
- `Dockerfile`       builds the image (php:8.3-apache + pdo_sqlite)
- `k8s/`             namespace + Deployment/Service/Ingress (Traefik)
- `.github/workflows/pipeline-runnerA.yml`  deploy via self-hosted runner on EC2
- `.github/workflows/pipeline-runnerB.yml`  deploy via kubeconfig secret (GitHub-hosted)

The app code is safe (prepared statements, output escaping). Pipeline:
build → docker scout scan → push ECR → deploy to K3s → Traefik ingress.

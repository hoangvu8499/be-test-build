pipeline {
    agent any

    options {
        timestamps()
        disableConcurrentBuilds()
        buildDiscarder(logRotator(numToKeepStr: '10'))
    }

    environment {
        DOCKERHUB_CREDENTIALS = credentials('jenkin-docker-hub')     // Jenkins credential ID (username + password/token)
        DOCKERHUB_REPO        = 'timovuton8499/be-php-test-build'
        IMAGE_TAG             = "${env.BUILD_NUMBER}"
        CONTAINER_NAME        = 'be-php'
        APP_PORT              = '8000'
    }

    stages {

        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Clean Workspace') {
            steps {
                sh '''
                    echo "Cleaning old build artifacts / caches..."
                    rm -rf build dist package.tar.gz
                    docker container prune -f || true
                '''
            }
        }

        stage('Lint / Static Analysis') {
            steps {
                sh '''
                    echo "Running PHP lint (via php:8.2-cli-alpine container, Jenkins agent has no php installed)..."
                    docker run --rm -v jenkins_home:/var/jenkins_home -w ${WORKSPACE} php:8.2-cli-alpine \
                        sh -c "find public -name '*.php' -print0 | xargs -0 -n1 php -l"
                '''
                // TODO: neu co composer.json, them:
                // sh 'composer install --no-interaction --prefer-dist'
                // sh 'vendor/bin/phpstan analyse || true'
                // sh 'vendor/bin/phpcs || true'
            }
        }

        stage('Test') {
            steps {
                echo 'Chua co test suite. TODO: them PHPUnit va chay o day, vi du:'
                echo '  vendor/bin/phpunit --testdox'
                // sh 'vendor/bin/phpunit --testdox'
            }
        }

        stage('Build') {
            steps {
                sh '''
                    echo "Build step (compile/prepare code)..."
                    mkdir -p build
                    cp -R public build/public
                    cp Dockerfile build/Dockerfile
                '''
            }
        }

        stage('Package') {
            steps {
                sh '''
                    echo "Packaging artifact..."
                    tar -czf package-${BUILD_NUMBER}.tar.gz -C build .
                '''
                archiveArtifacts artifacts: "package-${BUILD_NUMBER}.tar.gz", fingerprint: true
            }
        }

        stage('Build Docker Image') {
            steps {
                sh '''
                    docker build -t ${DOCKERHUB_REPO}:${IMAGE_TAG} -t ${DOCKERHUB_REPO}:latest .
                '''
            }
        }

        stage('Scan Docker Image') {
            steps {
                sh '''
                    echo "Scanning image for vulnerabilities (optional, requires trivy)..."
                    command -v trivy >/dev/null 2>&1 && trivy image --exit-code 0 --severity HIGH,CRITICAL ${DOCKERHUB_REPO}:${IMAGE_TAG} || echo "trivy not installed, skipping scan"
                '''
            }
        }

        stage('Push to Docker Hub') {
            steps {
                sh '''
                    echo "${DOCKERHUB_CREDENTIALS_PSW}" | docker login -u "${DOCKERHUB_CREDENTIALS_USR}" --password-stdin
                    docker push ${DOCKERHUB_REPO}:${IMAGE_TAG}
                    docker push ${DOCKERHUB_REPO}:latest
                    docker logout
                '''
            }
        }

        stage('Deploy') {
            steps {
                sh '''
                    echo "Deploying container locally..."
                    docker rm -f ${CONTAINER_NAME} || true
                    docker run -d --name ${CONTAINER_NAME} -p ${APP_PORT}:8000 --restart unless-stopped ${DOCKERHUB_REPO}:${IMAGE_TAG}
                '''
            }
        }

        stage('Health Check') {
            steps {
                sh '''
                    echo "Waiting for app to be ready..."
                    for i in $(seq 1 10); do
                        CONTAINER_IP=$(docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' ${CONTAINER_NAME})
                        if [ -n "$CONTAINER_IP" ] && docker run --rm curlimages/curl:latest -sf http://${CONTAINER_IP}:8000/health > /dev/null; then
                            echo "App is up!"
                            exit 0
                        fi
                        sleep 2
                    done
                    echo "Health check failed"
                    exit 1
                '''
            }
        }
    }

    post {
        success {
            echo "Pipeline succeeded: ${DOCKERHUB_REPO}:${IMAGE_TAG} deployed."
        }
        failure {
            echo "Pipeline failed. Rolling back is manual for now — consider adding an automatic rollback stage."
        }
        always {
            sh 'docker image prune -f || true'
            cleanWs()
        }
    }
}

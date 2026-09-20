pipeline {
    agent any

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Build Docker Image') {
            steps {
                sh 'docker build -t test-build-be:$BUILD_NUMBER -t test-build-be:latest .'
            }
        }

        stage('Deploy') {
            steps {
                sh '''
                    docker rm -f be || true
                    docker run -d --name be -p 8000:8000 test-build-be:latest
                '''
            }
        }
    }
}

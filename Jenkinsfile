
import com.tikal.jenkins.plugins.multijob.*
import hudson.*
import hudson.model.*
import hudson.plugins.git.*
import hudson.slaves.*
import hudson.tasks.*

// import java.net.HttpURLConnection
// import java.net.URL
// import java.io.File
// import java.io.OutputStream


//library('webdav-library') _
pipeline {
    agent any 
    stages {
        stage('Build') { 
            steps {
                 //sh "git log --name-only -n 1 HEAD~1..HEAD"
                // Define the command to execute
                script {
                      // Define file and WebDAV server details
                    def filePath = 'customer/development/views/pages/asknew.php'
                    def webdavUrl = 'https://coe-interview-4--d1--1.custhelp.com/dav/cp/' //'https://coe-interview-4--d1--1.custhelp.com/dav/cp/customer/development/controllers/' //'https://livetest-24c.custhelp.com/dav/cp/'
                    def webdavUsername = 'sreekanth' //'sreekanth'//'rntaccess'
                    def webdavPassword = 'Oracle@123' //'Oracle@123' //'T=,!d7p9M'
                    
                    
                    //URL="https://livetest-24c.custhelp.com/dav/cp/customer/development/views/pages/ask.php"
                    
                    checkout scmGit(branches: [[name: '*/main']], extensions: [], userRemoteConfigs: [[credentialsId: 'CICDWebHook', url: 'https://github.com/Prabinmca7/cp.git']])
                    // def command = "git diff-tree --no-commit-id --name-only -n 1 HEAD~1..HEAD -r"
                    // def filePaths = sh(script: command, returnStdout: true).trim().split('\n')
                    // //echo "File path: ${filePaths}"
                    // // Print or process each file path
                    // filePaths.each { path ->
                    // echo "File path: ${path}"

                    // def lastSlashIndex = path.lastIndexOf('/')
                    // def lastBackslashIndex = path.lastIndexOf('\\')
                    // // Determine the correct slash index
                    // def lastSlash = Math.max(lastSlashIndex, lastBackslashIndex)
                    // // Split the path
                    // def directory = path[0..lastSlash]
                    // def fileName = path[(lastSlash + 1)..-1]

                    //      sh """
                    //     curl -k -v -u ${webdavUsername}:${webdavPassword} -T ${path} ${webdavUrl}${directory}
                    //     """
                    // }
               
                  

                    // Upload the file using curl
                   println "success"
                   
                }
                
            }
         }
        stage('Test') { 
            steps {
               println "Test"
            }
        }
        stage('Deploy') { 
            steps {
                println "Deploy"
            }
        }
    }
}
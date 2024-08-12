#!groovy
import hudson.model.*
//Import global library
library 'devtools-global-library'

def ver = VersionNumber (versionNumberString: '${BUILD_DATE_FORMATTED, "1.yy.MM.dd"}-b${BUILDS_TODAY, XXXX}')
currentBuild.displayName = ver

//Check whether or not the build was triggered by Orahub by checking if the gitlabSourceBranch variable was set by the GitLab plugin
def SOURCE_BRANCH = "branch_undefined"
def TARGET_BRANCH = "branch_undefined"

//Artifactory variables
def ARTIFACTORY_WEBSITE = "https://artifacthub-iad.oci.oraclecorp.com"
def ARTIFACTORY_REPO = "osvc-release-local"
def ARTIFACTORY_TARBALLS_PATH = "osvc-portal/compiled-tarballs"
def ARTIFACTORY_CREDENTIAL_KEY = "osvccd_us_artifactory_iad_creds"
def HOST_ENDING = ".oraclecorp.com"

echo "gitlabSourceBranch: ${env.gitlabSourceBranch}  gitlabTargetBranch: ${env.gitlabTargetBranch}"
if ( env.gitlabSourceBranch == null ) {
    echo "Manually triggered"
    SOURCE_BRANCH = "${DefaultSourceBranch}"
    TARGET_BRANCH = "${DefaultTargetBranch}"

}else {
    echo "Triggered by Orahub"
    SOURCE_BRANCH = "${env.gitlabSourceBranch}"
    TARGET_BRANCH = "${env.gitlabTargetBranch}"
}

def BaseSiteName = "${SiteName.replace('site', '')}${BUILD_NUMBER}"
def flSiteName = "fl${BaseSiteName}"
def flsSiteName = "fls${BaseSiteName}"
def flwSiteName = "flw${BaseSiteName}"
def ServicesSiteName = "serv${BaseSiteName}"
def docSiteName = "doc${BaseSiteName}"
def rakeSiteName = "rake${BaseSiteName}"
def historySiteName = "hist${BaseSiteName}"
def jscSiteName = "jsc${BaseSiteName}"
def jswSiteName = "jsw${BaseSiteName}"
def renderingSiteName = "rend${BaseSiteName}"

timeout(time: PIPELINE_TIMEOUT_MINUTES.toInteger(), unit: 'MINUTES')
{
	try
	{
		node("${NODE_LABEL}")
		{
			env.JAVA_HOME="${tool 'Java8'}"
			env.PATH="${env.JAVA_HOME}/bin:${env.PATH}"

			stage('Build')
			{
			    // The deleteDir step will fail if any git branches have bad branch names. Need to use rm -rf for now
			    //delete workspace
			    //deleteDir()
			    sh "rm -rf ${env.WORKSPACE}/*"
			    sh 'pwd && ls'

			    echo "Running Git SCM: SOURCE_BRANCH is ${SOURCE_BRANCH} and gitlabTargetBranch is ${TARGET_BRANCH}"
			    orahubSCM {
                    repoUrl = "git@orahub.oci.oraclecorp.com:appdev-cloud-rnpd/server.git"
                    checkOutDir = "server"
                }
                orahubSCM {
                    repoUrl = "git@orahub.oci.oraclecorp.com:appdev-cloud-rnpd/common.git"
                    checkOutDir = "common"
                }
                orahubSCM {
                    repoUrl = "git@orahub.oci.oraclecorp.com:appdev-cloud-rnpd/cp.git"
                    checkOutDir = "scripts/cp"
                }

				sh "ls ${env.WORKSPACE}"
				sh "ls ${env.WORKSPACE}/scripts/cp"

				echo "This is the server build"
				// Clean
                sh "${env.WORKSPACE}/server/bin/enterChangeroot -j bash --norc -c '(export DEBUG=Y; source ${env.WORKSPACE}/server/bin/setupBuild -q ; make -C \\\$RNW/rnw clean -j13)'"
				// Server build
                sh "${env.WORKSPACE}/server/bin/enterChangeroot -j bash --norc -c '(export DEBUG=Y; source ${env.WORKSPACE}/server/bin/setupBuild -q ; make -C \\\$RNW/rnw PAPI_MODULES=\\\"ConnectPHP ConnectCPP\\\" all -j13)'"
				// double check we do not need to do MoveC{ in Hudson.Server.Build.xml
				// make cp
                sh "${env.WORKSPACE}/server/bin/enterChangeroot -j bash --norc -c '(export DEBUG=Y; source ${env.WORKSPACE}/server/bin/setupBuild -q ; make -j7 -C ${env.WORKSPACE}/scripts/cp mod_info.phph  RNT_BASE=${env.WORKSPACE}/server/src)'"

				// Get Build Number
				sh "ant -v extractBuildNumber -f scripts/cp/Jenkins.CP.BuildNumber.xml -DWORKSPACE=${env.WORKSPACE}"

				// preserve the compiled results for use by the v2 build - note that $JOB_NAME may include directories
				sh 'tar -zcf `basename $JOB_NAME-$BUILD_NUMBER.tar.gz` ./*'
				archiveArtifacts '*.tar.gz'

				stash includes: "", name: 'serverBuild'
				stash includes: 'scripts/cp/extras/testRunner/*, scripts/cp/extras/testRunner/lib/*, scripts/cp/extras/testRunner/runners/*, scripts/cp/extras/testRunner/templates/*', name: 'testrunner'
				//stash includes: 'Jenkins.CP.Analysis.xml, Jenkins.CP.BuildNumber.xml, Jenkins.CP.RakeBuild.xml', name: 'antfiles'
			}

			build job: "${DOWNSTREAM_JOB}", wait: false
		}

		parallel UnitTests:
		{
			node ("${NODE_LABEL}")
            {
                try
                {
                    stage ('FL Unit Tests')
                    {
                        def hostname = sh(returnStdout: true, script: 'hostname -f').trim().split('\\.')[0] + "${HOST_ENDING}"
                        echo "Hostname: ${hostname}"

                        sh "rm -rf ${env.WORKSPACE}/*"
                        unstash 'testrunner'
                        unstash 'serverBuild'

                        cpSiteBuild("${env.WORKSPACE}", "${flSiteName}", 0, "${hostname}")

                        // Deploy Site
                        sh "wget --header='Authorization: Basic YWRtaW46' --post-data='' --output-document=- http://${flSiteName}.${hostname}/ci/admin/deploy/removeDeployLock"
                        sh "wget --header='Authorization: Basic YWRtaW46' --post-data='' --output-document=- http://${flSiteName}.${hostname}/ci/admin/deploy"

                        def WEBDAV_REPORT = "${env.WORKSPACE}/WebDAVReport.txt"
                        def FLU_REPORT = "${env.WORKSPACE}/functionLevelUnit.html"
                        def PY_COMMAND = "python ${env.WORKSPACE}/scripts/cp/extras/testRunner/test.py --server=${hostname} --type=functional --subtype=standard --module=cp"

                        sh "rm -rf ${WEBDAV_REPORT}"
                        sh "rm -rf ${FLU_REPORT}"

                        sh "${env.WORKSPACE}/server/bin/enterChangeroot -j bash --norc -c '(export DEBUG=Y; source ${env.WORKSPACE}/server/bin/setupBuild -q ; TESTS=\\\"basic copymove locks http\\\" /nfs/local/linux/litmus/0.12.1/bin/litmus http://${flSiteName}.${hostname}/dav/cp/customer/development/views/pages/ admin \\\"\\\" >> ${WEBDAV_REPORT} 2>&1  )'"

                        sh "${PY_COMMAND} --htmlArtifact=${FLU_REPORT} ${flSiteName}"
                    }
                }
                catch (any)
                {
                    currentBuild.result = 'FAILURE'
                    throw any
                }
                finally
                {
                    archiveArtifacts artifacts: "WebDAVReport.txt", fingerprint: false

                    globalHTMLPublisher {
                        allowMissing = true
                        alwaysLinkToLastBuild = true
                        keepAll = true
                        reportDir = ""
                        reportFiles = "functionLevelUnit.html"
                        reportName = "Function Level Unit Test Report"
                        tmpDirPath = "/temptestdir"
                    }
                }
            }
        },
        FLSUnitTest:
        {
            node ("${NODE_LABEL}")
            {
                try
                {
                    stage ('FLS Unit Tests')
                    {
                        def hostname = sh(returnStdout: true, script: 'hostname -f').trim().split('\\.')[0] + "${HOST_ENDING}"
                        echo "Hostname: ${hostname}"

                        sh "rm -rf ${env.WORKSPACE}/*"
                        unstash 'testrunner'
                        unstash 'serverBuild'

                        cpSiteBuild("${env.WORKSPACE}", "${flsSiteName}", 0, "${hostname}")

                        // Deploy Site
                        sh "wget --header='Authorization: Basic YWRtaW46' --post-data='' --output-document=- http://${flsSiteName}.${hostname}/ci/admin/deploy/removeDeployLock"
                        sh "wget --header='Authorization: Basic YWRtaW46' --post-data='' --output-document=- http://${flsSiteName}.${hostname}/ci/admin/deploy"

                        def FLU_REPORT_SLOW = "${env.WORKSPACE}/functionLevelUnitSlow.html"
                        def PY_COMMAND_SLOW = "python ${env.WORKSPACE}/scripts/cp/extras/testRunner/test.py --server=${hostname} --type=functional --subtype=slow"

                        sh "rm -rf ${FLU_REPORT_SLOW}"
                        sh "${PY_COMMAND_SLOW} --htmlArtifact=${FLU_REPORT_SLOW} ${flsSiteName}"
                    }
                }
                catch (any)
                {
                    currentBuild.result = 'FAILURE'
                    throw any
                }
                finally
                {
                    globalHTMLPublisher {
                        allowMissing = true
                        alwaysLinkToLastBuild = true
                        keepAll = true
                        reportDir = ""
                        reportFiles = "functionLevelUnitSlow.html"
                        reportName = "Function Level Unit Slow Test Report"
                        tmpDirPath = "/temptestdir"
                    }
                }
            }
        },
        FLWUnitTest:
        {
            node ("${NODE_LABEL}")
            {
                try
                {
                    stage ('FLW Unit Tests')
                    {
                        def hostname = sh(returnStdout: true, script: 'hostname -f').trim().split('\\.')[0] + "${HOST_ENDING}"
                        echo "Hostname: ${hostname}"

                        sh "rm -rf ${env.WORKSPACE}/*"
                        unstash 'testrunner'
                        unstash 'serverBuild'

                        cpSiteBuild("${env.WORKSPACE}", "${flwSiteName}", 0, "${hostname}")

                        // Deploy Site
                        sh "wget --header='Authorization: Basic YWRtaW46' --post-data='' --output-document=- http://${flwSiteName}.${hostname}/ci/admin/deploy/removeDeployLock"
                        sh "wget --header='Authorization: Basic YWRtaW46' --post-data='' --output-document=- http://${flwSiteName}.${hostname}/ci/admin/deploy"

                        def FLU_REPORT_WIDGET = "${env.WORKSPACE}/functionLevelUnitWidget.html"
                        def PY_COMMAND_WIDGET = "python ${env.WORKSPACE}/scripts/cp/extras/testRunner/test.py --server=${hostname} --type=functional --subtype=widget --module=cp"

                        sh "rm -rf ${FLU_REPORT_WIDGET}"
                        sh "${PY_COMMAND_WIDGET} --htmlArtifact=${FLU_REPORT_WIDGET} ${flwSiteName}"
                    }
                }
                catch (any)
                {
                    currentBuild.result = 'FAILURE'
                    throw any
                }
                finally
                {
                    globalHTMLPublisher {
                        allowMissing = true
                        alwaysLinkToLastBuild = true
                        keepAll = true
                        reportDir = ""
                        reportFiles = "functionLevelUnitWidget.html"
                        reportName = "Function Level Unit Widget Report"
                        tmpDirPath = "/temptestdir"
                    }
                }
            }
		},
		ServicesUnitTests:
		{
			node ("${NODE_LABEL}")
			{
				try
				{
					stage ('Services Unit Tests')
					{
                        def hostname = sh(returnStdout: true, script: 'hostname -f').trim().split('\\.')[0] + "${HOST_ENDING}"
                        echo "Hostname: ${hostname}"

                        sh "rm -rf ${env.WORKSPACE}/*"
						unstash 'serverBuild'

                        cpSiteBuild("${env.WORKSPACE}", "${ServicesSiteName}", 360, "${hostname}")

						def SERVICES_REPORT = "${env.WORKSPACE}/ServicesReport.html"

						sh "rm -rf ${SERVICES_REPORT}"

						sh "bash -c 'export PATH_INFO=/custom/unitTest/ServicesUnitTest.php; export PATH_TRANSLATED=/custom/unitTest/ServicesUnitTest.php; export REQUEST_URI=/cgi-bin/${ServicesSiteName}.cfg/php/custom/unitTest/ServicesUnitTest.php; export REMOTE_ADDR=127.0.0.1; export LD_LIBRARY_PATH=/usr/lib:dll; export SCRIPT_NAME=/cgi-bin/${ServicesSiteName}.cfg; export SRCDIR=${env.WORKSPACE}; cd /bulk/httpd/cgi-bin/${ServicesSiteName}.cfg; ./php -q >> ${SERVICES_REPORT} 2>&1' "
					}
				}
				catch (any)
				{
					currentBuild.result = 'FAILURE'
					throw any
				}
				finally
				{
					globalHTMLPublisher {
						allowMissing = true
						alwaysLinkToLastBuild = true
						keepAll = true
						reportDir = ""
						reportFiles = "ServicesReport.html"
						reportName = "Services Unit Tests Report"
						tmpDirPath = "/temptestdir"
					}
				}
			}
		},
		Rake:
		{
			node ("${NODE_LABEL}")
			{
				try
				{
                    testsPassed = true
					stage ('Rake')
					{
                        def hostname = sh(returnStdout: true, script: 'hostname -f').trim().split('\\.')[0] + "${HOST_ENDING}"
                        echo "Hostname: ${hostname}"

                        sh "rm -rf ${env.WORKSPACE}/*"
						unstash 'testrunner'
						unstash 'serverBuild'

                        cpSiteBuild("${env.WORKSPACE}", "${rakeSiteName}", 240, "${hostname}")

						sh "${env.WORKSPACE}/server/bin/enterChangeroot -j bash --norc -c '(export DEBUG=Y; source ${env.WORKSPACE}/server/bin/setupBuild -q ; ant -v RakeCP -f scripts/cp/Jenkins.CP.RakeBuild.xml -DWORKSPACE=${env.WORKSPACE} -DSiteName=${rakeSiteName} -DGitBranch=${TARGET_BRANCH} -DBuildNumber=${env.BUILD_NUMBER} -DServer=${hostname})'"
					}
				}
				catch (any)
				{
					currentBuild.result = 'FAILURE'
                    testsPassed = false
					throw any
				}
				finally
				{
                    if (testsPassed)
					{
                        //delete old tarballs from Artifactory by reading tarballsToRemove.txt file and hitting Artifactory delete api endpoint for each entry.
                        tarballsToRemove = readFile('./cp/artifactory/tarballsToRemove.txt').split()
                        for (tarball in tarballsToRemove) {
                            echo "Removing tarball: ${tarball}"
                            withCredentials([usernamePassword(credentialsId: "${ARTIFACTORY_CREDENTIAL_KEY}", passwordVariable: 'artifactory_password', usernameVariable: 'artifactory_user')]) {
                                sh "/usr/bin/curl -s -u $artifactory_user:$artifactory_password -XDELETE \"${ARTIFACTORY_WEBSITE}/${ARTIFACTORY_REPO}/${ARTIFACTORY_TARBALLS_PATH}/${tarball}\" "
                            }
                        }
                        //upload new tarballs to Artifactory by reading the tarballsToUpload folder and hitting Artifactory deploy artifact api endpoint for each entry.
                        tarballsToUpload = findFiles(glob: 'cp/artifactory/tarballsToUpload/**')
                        for (tarball in tarballsToUpload) {
                            echo "Uploading tarball: ${tarball.name}"
                            withCredentials([usernamePassword(credentialsId: "${ARTIFACTORY_CREDENTIAL_KEY}", passwordVariable: 'artifactory_password', usernameVariable: 'artifactory_user')]) {
                                sh "/usr/bin/curl -s -u $artifactory_user:$artifactory_password -XPUT -T ${tarball.path} \"${ARTIFACTORY_WEBSITE}/${ARTIFACTORY_REPO}/${ARTIFACTORY_TARBALLS_PATH}/${tarball.name}\" "
                            }
                        }
					}
				}
			}
		},
		Documentation:
		{
			node ("${NODE_LABEL}")
			{
				try
				{
					stage ('Documentation')
					{
                        def hostname = sh(returnStdout: true, script: 'hostname -f').trim().split('\\.')[0] + "${HOST_ENDING}"
                        echo "Hostname: ${hostname}"

                        sh "rm -rf ${env.WORKSPACE}/*"
						unstash 'serverBuild'

                        cpSiteBuild("${env.WORKSPACE}", "${docSiteName}", 480, "${hostname}")

						def DOCUMENTATION_REPORT = "${env.WORKSPACE}/DocumentationReport.html"

						sh "rm -rf ${DOCUMENTATION_REPORT}"

						sh "bash -c 'export REQUEST_METHOD=GET; export PATH_INFO=/bootstrap/index.php; export PATH_TRANSLATED=/bootstrap/index.php; export REQUEST_URI=/ci/unitTest/documentation/php; export REMOTE_ADDR=127.0.0.1; export LD_LIBRARY_PATH=/usr/lib:dll; export SCRIPT_NAME=/cgi-bin/${docSiteName}.cfg/php; cd /bulk/httpd/cgi-bin/${docSiteName}.cfg; ./php -q >> ${DOCUMENTATION_REPORT} 2>&1' "
					}
				}
				catch (any)
				{
					currentBuild.result = 'FAILURE'
					throw any
				}
				finally
				{
						globalHTMLPublisher {
							allowMissing = true
							alwaysLinkToLastBuild = true
							keepAll = true
							reportDir = ""
							reportFiles = "DocumentationReport.html"
							reportName = "Documentation Report"
							tmpDirPath = "/temptestdir"
						}
				}
			}
		},
		Analysis:
		{
			node ("${NODE_LABEL}")
			{
				try
				{
					stage ('Analysis')
					{
                        sh "rm -rf ${env.WORKSPACE}/*"
						unstash 'testrunner'
						unstash 'serverBuild'

						sh "${env.WORKSPACE}/server/bin/enterChangeroot -j bash --norc -c '(export DEBUG=Y; source ${env.WORKSPACE}/server/bin/setupBuild -q ; ant -v RunCPAnalysisDepends -f scripts/cp/Jenkins.CP.Analysis.xml -DWORKSPACE=${env.WORKSPACE})'"
					}
				}
				catch (any)
				{
					currentBuild.result = 'FAILURE'
					throw any
				}
				finally
				{
					archiveArtifacts artifacts: 'cpLintOutput.txt', fingerprint: false

					globalHTMLPublisher {
						allowMissing = true
						alwaysLinkToLastBuild = true
						keepAll = true
						reportDir = ""
						reportFiles = "codeSniffer.html"
						reportName = "CodeSniffer Report"
						tmpDirPath = "/temptestdir"
					}
				}
			}
		},
		RenderingUnitTests:
        {
            node ("${NODE_LABEL}")
            {
                try
                {
                    stage ('Rendering Unit Tests')
                    {
                        def hostname = sh(returnStdout: true, script: 'hostname -f').trim().split('\\.')[0] + "${HOST_ENDING}"
                        echo "Hostname: ${hostname}"

                        sh "rm -rf ${env.WORKSPACE}/*"
                        unstash 'testrunner'
                        unstash 'serverBuild'

                        cpSiteBuild("${env.WORKSPACE}", "${renderingSiteName}", 360, "${hostname}")

                        def CPDEPLOY_REPORT = "${env.WORKSPACE}/deploy.html"
                        def RENDERING_REPORT = "${env.WORKSPACE}/rendering.html"
                        def PY_RENDERING_COMMAND = "python ${env.WORKSPACE}/scripts/cp/extras/testRunner/test.py --server=${hostname} --type=rendering --deploy=false"

                        sh "rm -rf ${CPDEPLOY_REPORT}"
                        sh "rm -rf ${RENDERING_REPORT}"
                        sh "python ${env.WORKSPACE}/scripts/cp/extras/testRunner/test.py --server=${hostname} --type=deploy --htmlArtifact=${env.WORKSPACE}/deploy.html ${renderingSiteName}"
                        sh "${PY_RENDERING_COMMAND} --htmlArtifact=${RENDERING_REPORT} ${renderingSiteName}"
                    }
                }
                catch (any)
                {
                    currentBuild.result = 'FAILURE'
                    throw any
                }
                finally
                {
                    globalHTMLPublisher {
                        allowMissing = true
                        alwaysLinkToLastBuild = true
                        keepAll = true
                        reportDir = ""
                        reportFiles = "deploy.html"
                        reportName = "Deploy Report"
                        tmpDirPath = "/temptestdir"
                    }

                    globalHTMLPublisher {
                        allowMissing = true
                        alwaysLinkToLastBuild = true
                        keepAll = true
                        reportDir = ""
                        reportFiles = "rendering.html"
                        reportName = "Rendering Unit Tests Report"
                        tmpDirPath = "/temptestdir"
                    }
                }
            }
        },
        JSCUnitTests:
        {
            node ("${NODE_LABEL}")
            {
                try
                {
                    stage ('JSC Unit Tests')
                    {
                        def hostname = sh(returnStdout: true, script: 'hostname -f').trim().split('\\.')[0] + "${HOST_ENDING}"
                        echo "Hostname: ${hostname}"

                        sh "rm -rf ${env.WORKSPACE}/*"
                        unstash 'testrunner'
                        unstash 'serverBuild'

                        cpSiteBuild("${env.WORKSPACE}", "${jscSiteName}", 180, "${hostname}")

                        // Deploy Site
                        sh "wget --header='Authorization: Basic YWRtaW46' --post-data='' --output-document=- http://${jscSiteName}.${hostname}/ci/admin/deploy/removeDeployLock"
                        sh "wget --header='Authorization: Basic YWRtaW46' --post-data='' --output-document=- http://${jscSiteName}.${hostname}/ci/admin/deploy"

                        def JSC_REPORT = "${env.WORKSPACE}/jsc.html"
                        def PY_JSC_COMMAND = "python ${env.WORKSPACE}/scripts/cp/extras/testRunner/test.py --server=${hostname} --type=javascript --subtype=core --browser=phantom --skipGenerate=false"

                        sh "rm -rf ${JSC_REPORT}"
                        sh "${PY_JSC_COMMAND} --htmlArtifact=${JSC_REPORT} ${jscSiteName}"
                    }
                }
                catch (any)
                {
                    currentBuild.result = 'FAILURE'
                    throw any
                }
                finally
                {
                    globalHTMLPublisher {
                        allowMissing = true
                        alwaysLinkToLastBuild = true
                        keepAll = true
                        reportDir = ""
                        reportFiles = "jsc.html"
                        reportName = "JavaScript Core Unit Tests Report"
                        tmpDirPath = "/temptestdir"
                    }
                }
            }
        },
        JSWUnitTests:
        {
            node ("${NODE_LABEL}")
            {
                try
                {
                    stage ('JSW Unit Tests')
                    {
                        def hostname = sh(returnStdout: true, script: 'hostname -f').trim().split('\\.')[0] + "${HOST_ENDING}"
                        echo "Hostname: ${hostname}"

                        sh "rm -rf ${env.WORKSPACE}/*"
                        unstash 'testrunner'
                        unstash 'serverBuild'

                        cpSiteBuild("${env.WORKSPACE}", "${jswSiteName}", 180, "${hostname}")

                        // Deploy Site
                        sh "wget --header='Authorization: Basic YWRtaW46' --post-data='' --output-document=- http://${jswSiteName}.${hostname}/ci/admin/deploy/removeDeployLock"
                        sh "wget --header='Authorization: Basic YWRtaW46' --post-data='' --output-document=- http://${jswSiteName}.${hostname}/ci/admin/deploy"

                        def JSW_REPORT = "${env.WORKSPACE}/jsw.html"
                        def PY_JSW_COMMAND = "python ${env.WORKSPACE}/scripts/cp/extras/testRunner/test.py --server=${hostname} --type=javascript --subtype=widget --browser=phantom --skipGenerate=false"

                        sh "rm -rf ${JSW_REPORT}"
                        sh "${PY_JSW_COMMAND} --htmlArtifact=${JSW_REPORT} ${jswSiteName}"
                    }
                }
                catch (any)
                {
                    currentBuild.result = 'FAILURE'
                    throw any
                }
                finally
                {
                    globalHTMLPublisher {
                        allowMissing = true
                        alwaysLinkToLastBuild = true
                        keepAll = true
                        reportDir = ""
                        reportFiles = "jsw.html"
                        reportName = "JavaScript Widget Unit Tests Report"
                        tmpDirPath = "/temptestdir"
                    }
                }
            }
        },
        cpHistory:
        {
            node ("${NODE_LABEL}")
            {
                try
                {
                    stage ('cpHistory')
                    {
                        def hostname = sh(returnStdout: true, script: 'hostname -f').trim().split('\\.')[0] + "${HOST_ENDING}"
                        echo "Hostname: ${hostname}"

                        sh "rm -rf ${env.WORKSPACE}/*"
                        unstash 'testrunner'
                        unstash 'serverBuild'

                        cpSiteBuild("${env.WORKSPACE}", "${historySiteName}", 0, "${hostname}")

                        def HISTORY_REPORT = "${env.WORKSPACE}/history.html"
                        def HISTORY_CLONE_COMMAND = "bash -c 'cd ${env.WORKSPACE}/scripts/cp && /bin/bash ./extras/utils/validateCPHistory.bash ${env.WORKSPACE}/scripts/cp'"
                        def HISTORY_COMPARE_COMMAND = "bash -c 'export PATH_INFO=/bootstrap/index.php;export PATH_TRANSLATED=/bootstrap/index.php;export REQUEST_URI=/ci/unitTest/ValidateCPHistory;export REMOTE_ADDR=127.0.0.1;export LD_LIBRARY_PATH=/usr/lib:dll;export SCRIPT_NAME=/cgi-bin/${historySiteName}.cfg;export SRCDIR=${env.WORKSPACE}/${historySiteName};cd /bulk/httpd/cgi-bin/${historySiteName}.cfg;./php -q >> ${HISTORY_REPORT} 2>&1'"

                        sh "${HISTORY_CLONE_COMMAND} >> ${HISTORY_REPORT} 2>&1"
                        sh "${HISTORY_COMPARE_COMMAND}"
                    }
                }
                catch (any)
                {
                    currentBuild.result = 'FAILURE'
                    throw any
                }
                finally
                {
                    globalHTMLPublisher {
                        allowMissing = true
                        alwaysLinkToLastBuild = true
                        keepAll = true
                        reportDir = ""
                        reportFiles = "history.html"
                        reportName = "cpHistory Report"
                        tmpDirPath = "/temptestdir"
                    }
                }
            }
        }
		failFast: false
	}
	catch (any)
	{
		currentBuild.result = 'FAILURE'
		throw any
	}
	finally
	{
		//Set current build result
		def myResult = currentBuild.rawBuild.getResult()
		//Set previous build result
		def lastResult = currentBuild.rawBuild.getPreviousBuild()?.getResult()
		if ("${myResult}" == "FAILURE") {
				echo "Post build actions for a failure"
		}
		//email global library
		globalMail {
		  emailRecipients = ''
		  buildResult = "${myResult}"
		  previousResult = "${lastResult}"
		}
	}
}

def cpSiteBuild(String sourcePath, String siteName, int sleepTimeSec, String thisNode, String createDbArgs="-cannedSPM -noAccessControl ${siteName}") {
    // Sleep for ${sleepTimeSec} seconds so that site builds don't conflict and fail
    sh "sleep ${sleepTimeSec}"

    // run create_test_site
    sh "${sourcePath}/server/bin/enterChangeroot -j bash --norc -c '(export DEBUG=Y; source ${sourcePath}/server/bin/setupBuild -q -s ${siteName} ; create_test_site -papisite -cp-path ${sourcePath}/scripts/cp ${sourcePath}/server/src ${siteName})'"

    // restart apache
    sh "/etc/init.d/restart_httpd || true"

    // run create_test_db
    sh "${sourcePath}/server/bin/enterChangeroot -j bash --norc -c '(export DEBUG=Y; source ${sourcePath}/server/bin/setupBuild -q -s ${siteName} ; create_test_db ${createDbArgs})'"

    // set config SEC_INTEGRATION_NETWORK_BLOCKLIST to null to allow fsockopen calls
    sh "${sourcePath}/server/bin/enterChangeroot -j bash --norc -c '(export DEBUG=Y; source ${sourcePath}/server/bin/setupBuild -q -s ${siteName} ; set_config ${siteName} SEC_INTEGRATION_NETWORK_BLOCKLIST \\\"\\\" )'"

    // set config OE_WEB_SERVER to ${siteName}.${thisNode} so calls to getConfig(OE_WEB_SERVER) return the correct value
    sh "${sourcePath}/server/bin/enterChangeroot -j bash --norc -c '(export DEBUG=Y; source ${sourcePath}/server/bin/setupBuild -q -s ${siteName} ; set_config ${siteName} OE_WEB_SERVER \\\"${siteName}.${thisNode}\\\" )'"

    // set config CACHED_CONTENT_SERVER to ${siteName}.${thisNode} so calls to getConfig(CACHED_CONTENT_SERVER) return the correct value
    sh "${sourcePath}/server/bin/enterChangeroot -j bash --norc -c '(export DEBUG=Y; source ${sourcePath}/server/bin/setupBuild -q -s ${siteName} ; set_config ${siteName} CACHED_CONTENT_SERVER \\\"${siteName}.${thisNode}\\\" )'"

    // check site URL
    sh "cd ${sourcePath} && curl \"http://${siteName}.${thisNode}/\" -o \"${siteName}CurlOut.html\" -w \"%{http_code}\""
}

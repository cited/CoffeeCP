#!/bin/bash

COFFEECP_HOME='/usr/coffeecp'
FINAL_JSON='{\n'

function build_servers_list(){

	FINAL_JSON+='"servers": [\n'
	DELIMITER='';

	for f in $(ls "${COFFEECP_HOME}/appfiles"); do
		type=${f%%-*}
		case $type in
			apache)
				version=$(echo ${f} | sed 's/.*apache-tomcat-\([0-9\.]\+\).zip$/\1/')
				FINAL_JSON+="${DELIMITER}{ \"version\": \"${version}\", \"name\": \"Tomcat\", \"type\": 1, \"folder_name\": \"apache-tomcat-${version:0:1}x\", \"path\": \"${COFFEECP_HOME}/appfiles/apache-tomcat-${version}.zip\"}"
				;;
			wildfly)
				version=$(echo ${f} | sed 's/.*wildfly-\([a-zA-Z0-9\.]\+\).zip$/\1/')
				FINAL_JSON+="${DELIMITER}{ \"version\": \"${version}\", \"name\": \"WildFly\", \"type\": 2, \"folder_name\": \"wildfly-${version:0:2}x\", \"path\": \"${COFFEECP_HOME}/appfiles/wildfly-${version}.zip\"}"
				;;
			glassfish)
				version=$(echo ${f} | sed 's/.*glassfish-\([0-9\.]\+\).zip$/\1/')
				FINAL_JSON+="${DELIMITER}{ \"version\": \"${version}\", \"name\": \"GlassFish\", \"type\": 3, \"folder_name\": \"glassfish${version:0:1}\", \"path\": \"${COFFEECP_HOME}/appfiles/glassfish-${version}.zip\"}"
				;;
			*)
				echo "Error: Unknown file type for ${f}"
				;;
		esac
		DELIMITER=','
	done

	FINAL_JSON+=' ]\n'
}

function build_jdk_list(){
	FINAL_JSON+=',"JDK": ['

	DELIMITER=''
for f in $(ls -d /usr/lib/jvm/jre-[0-9\.]*-openjdk); do
  version=$(echo ${f} | sed 's/.*jre-\([0-9\.]\+\)-openjdk$/\1/')
  FINAL_JSON+="${DELIMITER}{\"version\":\"${version}\", \"name\":\"OpenJDK\", \"path\":\"/usr/lib/jvm/jre-${version}-openjdk\"}"
  DELIMITER=','
done
	FINAL_JSON+=']'
	return 0;
}


build_servers_list;
build_jdk_list;

FINAL_JSON+='\n}'

echo -e "${FINAL_JSON}" > /usr/coffeecp/json/coffeecp_server.json

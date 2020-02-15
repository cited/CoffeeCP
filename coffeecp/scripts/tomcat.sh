#!/bin/bash
export CATALINA_HOME=#TOMCAT_PATH#
RETVAL=$?
function start(){
	if [ -z "${jkacugis_PID}" -a -f $CATALINA_HOME/bin/startup.sh ]; then
		echo "Starting jkacugis"
		$CATALINA_HOME/bin/startup.sh
	fi
	RETVAL=$?
}

function stop(){
	if [ "${jkacugis_PID}" -a -f $CATALINA_HOME/bin/shutdown.sh ]; then
		echo "Stopping jkacugis"
		$CATALINA_HOME/bin/shutdown.sh

		#Wait at most 10 second for jkacugis to stop
		COUNTER=10
		jkacugis_PID=$(ps axww | grep jkacugis | grep java | grep -v grep | awk '{print $1}');
		while [ "${jkacugis_PID}" -a $COUNTER -gt 0 ]; do
			sleep 1;
			jkacugis_PID=$(ps axww | grep jkacugis | grep java | grep -v grep | awk '{print $1}');
			let COUNTER=COUNTER-1
		done
	fi
	RETVAL=$?
}

jkacugis_PID=$(ps axww | grep jkacugis | grep java | grep -v grep | awk '{print $1}');

case "$1" in
 start)
		start;
        ;;
 stop)
		stop;
        ;;
 restart)
		echo "Restarting jkacugis"
        stop;
        jkacugis_PID='';
        start;
        ;;
 status)
		if [ "${jkacugis_PID}" ]; then
			echo "jkacugis is running with PID ${jkacugis_PID}";
			RETVAL=1
		else
			echo "jkacugis is not running";
			RETVAL=0
		fi
		;;
 *)
        echo $"Usage: $0 {start|stop|restart|status}"
        exit 1
        ;;
esac
exit $RETVAL

#!/bin/bash

echo "### Tor Router ###"

# detect wordpress directory
PLUGINDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
WPDIR=$(echo "$PLUGINDIR" | rev | cut -d'/' -f4- | rev)

if [ ! -d "$WPDIR/wp-content" ]; then
	echo "# ERROR: Wordpress directory not found. Edit this script if you are using a non-default directory structure."
	exit
fi

if [ -f "$WPDIR/wp-content/tor-router-cron" ]; then
	CURTS=$(date +%s)
	CRONTS=`cat $WPDIR/wp-content/tor-router-cron`
	# check for Tor restart trigger
	if [ "$CRONTS" == "restart" ]; then
		echo "# restarting Tor ..."
		systemctl restart tor
		echo "$CURTS" > "$WPDIR/wp-content/tor-router-cron"
	else
		#test connection if activated and last check older than 1 hour
		TSDIFF=$((CURTS-CRONTS))
		if (( $TSDIFF < 3600 )); then
			echo "# No Tor Router Job found."
		else
			echo "$CURTS" > "$WPDIR/wp-content/tor-router-cron"
			if [ -r "$WPDIR/wp-config.php" ]; then
				SQLHOSTR=$( awk '/DB_HOST/ {print $3}' $WPDIR/wp-config.php )
				SQLHOST=${SQLHOSTR#"'"}
				SQLHOST=${SQLHOST%"'"}
				SQLDBR=$( awk '/DB_NAME/ {print $3}' $WPDIR/wp-config.php )
				SQLDB=${SQLDBR#"'"}
				SQLDB=${SQLDB%"'"}
				SQLUSERR=$( awk '/DB_USER/ {print $3}' $WPDIR/wp-config.php )
				SQLUSER=${SQLUSERR#"'"}
				SQLUSER=${SQLUSER%"'"}
				SQLPASSR=$( awk '/DB_PASSWORD/ {print $3}' $WPDIR/wp-config.php )
				SQLPASS=${SQLPASSR#"'"}
				SQLPASS=${SQLPASS%"'"}
				WPSITEURL=$(mysql -h$SQLHOST -D$SQLDB -u$SQLUSER -p$SQLPASS -se "SELECT option_value FROM wp_options WHERE option_name = 'siteurl'")
				if [ "$WPSITEURL" == "" ]; then
					echo "# ERROR: Could not connect / retrieve data from the WordPress database. Exiting."
					exit
				else
					TRTORKEEPALIVE=$(mysql -h$SQLHOST -D$SQLDB -u$SQLUSER -p$SQLPASS -se "SELECT option_value FROM wp_options WHERE option_name = 'torrouter_torkeepalive'")
					if [ "$TRTORKEEPALIVE" == "1" ]; then
						TRHOST=$(mysql -h$SQLHOST -D$SQLDB -u$SQLUSER -p$SQLPASS -se "SELECT option_value FROM wp_options WHERE option_name = 'torrouter_host'")
						TRPORT=$(mysql -h$SQLHOST -D$SQLDB -u$SQLUSER -p$SQLPASS -se "SELECT option_value FROM wp_options WHERE option_name = 'torrouter_port'")
						#install curl
						if [ ! -n "$(command -v curl)" ]; then
							if [ -n "$(command -v yum)" ]; then
								PMGR="yum"
							else
								PMGR="apt-get"
							fi
							echo "# installing curl ..."
							$PMGR -y install curl
						fi
						echo "# testing connection to $WPSITEURL via Proxy ($TRHOST:$TRPORT) ..."
						CHRES=$(curl --connect-timeout 50 --location --socks5-hostname $TRHOST:$TRPORT $WPSITEURL/wp-content/tor-router-cron)
						if [ "$CHRES" == "" ]; then
							echo "# testing connection to https://icanhazip.com via Proxy ($TRHOST:$TRPORT) ..."
							CHRES2=$(curl --connect-timeout 50 --location --socks5-hostname $TRHOST:$TRPORT https://icanhazip.com)
							if [ "$CHRES2" == "" ]; then
								echo "# Connection failed. Restarting Tor ..."
								systemctl restart tor
							fi
						else
							echo "# Test successful."
						fi
					else
						echo "# Tor KeepAlive not activated. Doing nothing."
					fi
				fi
			else
				echo "# ERROR: Could not retrieve SQL credentials from wp-config.php. Exiting."
				exit
			fi
		fi
	fi
	touch "$WPDIR/wp-content/tor-router-cron"
else
	CURTS=$(date +%s)
	echo "$CURTS" > "$WPDIR/wp-content/tor-router-cron"
	chmod 777 "$WPDIR/wp-content/tor-router-cron"
fi

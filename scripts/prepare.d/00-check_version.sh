#!/bin/bash
#
# Server side version check
#

function verToNr()
{
  local r="0";
  local n=0
  IFS=$'.'
  for nr in $1; do
    n=$((n+1))
  done
  n=5
  for nr in $1; do
    r="$r + 100^$n*$nr"
    n=$((n-1))
  done
  unset IFS
  echo "$r" | bc
}

function version_compare()
{
  local ver=$(verToNr "$1")
  local wants="$2"
  [[ "$wants" =~ ([\>,\<,\=]+)([0-9,\.]+) ]]
  local op="${BASH_REMATCH[1]}"
  local cver="$(verToNr ${BASH_REMATCH[2]})"
  (($ver $op $cver))
}

function check_version()
{
  actionInfo="${actionConfig/ticket\/config\/\{token\}/config\/info}"
  jsonInfo="$(${wget} ${wgetOptions} -qO- "${actionInfo}")"

  retval=$?
  if [ ${retval} -eq 0 ]; then
    # check version
    >&2 echo "check version"
    client_version="$(dpkg-query --showformat='${Version}' --show lernstick-exam-client)"
    lernstick_version="$(grep -oP '[0-9,\-]{8,}' /usr/local/lernstick.html | sed 's/-//g' | head -1)"
    wants_client_version="$(echo "$jsonInfo" | ${python} -c 'import sys, json; print json.load(sys.stdin)["wants_client_version"]')"
    wants_lernstick_version="$(echo "$jsonInfo" | ${python} -c 'import sys, json; print json.load(sys.stdin)["wants_lernstick_version"]')"
    >&2 echo "client_version = $client_version"
    >&2 echo "wants_server_version = $wants_server_version"
    >&2 echo "wants_client_version = $wants_client_version"

    if ! version_compare "$lernstick_version" "$wants_lernstick_version"; then
      >&2 echo "Lernstick version mismatch. Got ${lernstick_version}, but server needs ${wants_lernstick_version}."

      export zenity
      export lernstick_version
      export wants_lernstick_version
      screen -d -m bash -c '${zenity} --error --width=300 --title "Version Error" --text "Lernstick version mismatch. Got ${lernstick_version}, but server needs ${wants_lernstick_version}."'
      do_exit 1
    fi
    if ! version_compare "$client_version" "$wants_client_version"; then
      >&2 echo "Client version mismatch. Got ${client_version}, but server needs ${wants_client_version}."

      export zenity
      export client_version
      export wants_client_version
      screen -d -m bash -c '${zenity} --error --width=300 --title "Version Error" --text "Client version mismatch. Got ${client_version}, but server needs ${wants_client_version}."'
      do_exit 1
    fi
  else
    >&2 echo "wget failed while fetching the system info (return value: ${retval})."

    export zenity
    export retval
    screen -d -m bash -c '${zenity} --error --width=300 --title "Wget error" --text "wget failed while fetching the system info (return value: ${retval})."'
    do_exit 1
  fi
}

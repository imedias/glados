#!/bin/bash
#
# Install the /usr/bin/max_brightness script
#

function max_brightness()
{
  value="$1"
  if [ "${value}" != "100" ] && [ "${value}" != "" ]; then
    chroot ${initrd}/newroot systemctl enable max_brightness.service
  fi
}

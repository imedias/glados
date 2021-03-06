#!/bin/bash
#
# Setup for screen_capture
#

function screen_capture()
{
  # config->screen_capture
  if [ "$(config_value "screen_capture")" = "True" ]; then

    c="$(config_value "screen_capture_chunk")"
    chunk="${c:-"10"}"

    c="$(config_value "screen_capture_overflow_threshold")"
    threshold="${c:-"500m"}"

    c="$(config_value "screen_capture_path")"
    path="${c:-"/home/user/ScreenCapture"}"

    command="$(config_value "screen_capture_command")"

    if [ "${command}" != "" ]; then

    chroot ${initrd}/newroot mkdir "${path}"
    chroot ${initrd}/newroot systemctl enable screen_capture.service

    chroot ${initrd}/newroot ln -s /var/log/screen_capture.log "${path}"/screen_capture.log

    # setup the launch timer
    cat <<EOF3 >>"${initrd}/newroot/etc/launch.conf"
# screen_capture
name+=("screen_capture")
threshold+=("${threshold}")
path+=("${path}")
hardlink+=("@(*.m3u8|*.log)")
move+=("*.ts")
remove+=("*.ts")
log+=("screen_capture.log")
chunk+=("${chunk}")

EOF3

    fi
  fi
}

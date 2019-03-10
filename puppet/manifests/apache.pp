# --installed by module
# service { "httpd":
#   ensure  => "running",
#   require => Package["httpd"],
# }

# -- created by module
# group { 'apache':
#   ensure => 'present',
#   gid    => 1001,
# }

class { 'apache': default_vhost => false }

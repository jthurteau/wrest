file { '/var/log/httpd' :
  ensure  => directory,
  group => 'vagrant',
  recurse => true,
  require => Package['httpd'],
}

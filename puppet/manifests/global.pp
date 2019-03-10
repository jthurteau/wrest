exec { 'yum update -y' :
  path => '/usr/bin',
}

file { "${app_path}" :
  ensure  => directory,
  require => Package['httpd'],
}

file { "${vagrant_root}" :
  ensure  => directory,
  group => "${httpd_group}",
  mode => '0755',
  require => Package['httpd'],
}

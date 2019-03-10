group { 'ncsu' :
  ensure => 'present',
  gid    => 1011,
}

file { "${app_vendor_path}" :
  ensure  => directory,
  require => File["${app_path}"],
  group => 'ncsu',
}

file { "${app_path}/library" :
  ensure  => directory,
  require => File["${app_path}"],
  group => 'ncsu',
}

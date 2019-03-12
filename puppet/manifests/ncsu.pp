group { 'ncsu' :
  ensure => 'present',
  gid    => 1011,
}

user { 'vagrant' :
  ensure => 'present',
  groups => ['ncsu']
}


file { "${app_vendor_path}" :
  ensure  => directory,
  require => File["${app_path}"],
}

file { "${app_path}/library" :
  ensure  => directory,
  require => File["${app_path}"],
}

# file { '${app_vendor_path}/${app}/public/vendor':
#   ensure  => directory,
#   require => File["${app_vendor_path}/${app}"],
# }

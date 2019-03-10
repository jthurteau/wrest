vcsrepo { "${app_vendor_path}/Zend" :
  ensure   => present,
  provider => git,
  source   => 'git://github.com/zendframework/zf1.git',
  require => File["${app_vendor_path}"],
}
# file { '/var/www/application/rooms/public/vendor':
#   ensure  => directory,
#   require => File["${app_vendor_path}"],
#   group => 'ncsu',
# }

file { "${app_path}/library/Zend" :
  ensure  => link,
  force   => true,
  target  => "${app_vendor_path}/Zend/library/Zend",
  require => [File["${app_path}/library"], Vcsrepo["${app_vendor_path}/Zend"]],
}

# vcsrepo { '/var/www/application/library/Saf':
#   ensure   => present,
#   provider => git,
#   source   => 'git://github.com/jthurteau/saf.git',
#   require => File['/var/www/application/library'],
# }
# file { '/var/www/application/library/Saf':
#   ensure  => link,
#   force   => true,
#   target  => '/saf',
#   require => [File['/var/www/application/library']],
# }

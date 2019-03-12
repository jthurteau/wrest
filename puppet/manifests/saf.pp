# vcsrepo { "${app_vendor_path}/Zend" :
#   ensure   => present,
#   provider => git,
#   source   => 'git://github.com/zendframework/zf1.git',
#   require => File["${app_vendor_path}"],
# }

vcsrepo { "${app_vendor_path}/Zend" :
  ensure   => present,
  provider => git,
  force   => true,
  source   => 'git://github.com/zendframework/zendframework.git',
  require => File["${app_vendor_path}"],
}

# vcsrepo { "${app_vendor_path}/Laravel" :
#   ensure   => present,
#   provider => git,
#   source   => 'git://github.com/zendframework/zf1.git',
#   require => File["${app_vendor_path}"],
# }

file { "${app_path}/library/Zend" :
  ensure  => link,
  force   => true,
  target  => "${app_vendor_path}/Zend/library/Zend",
  require => [File["${app_path}/library"], Vcsrepo["${app_vendor_path}/Zend"]],
}

# vcsrepo { "${app_path}/library/Saf" :
#   ensure   => present,
#   provider => git,
#   source   => 'git://github.com/jthurteau/saf.git',
#   require => File["${app_path}/library"],
#   revision => "php7",
# }

file { "${app_path}/library/Saf" :
  ensure  => link,
  force   => true,
  target  => "${vagrant_root}/lib/Saf",
  require => [File["${app_path}/library"],File["${vagrant_root}"]],
}

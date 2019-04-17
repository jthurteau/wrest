vcsrepo { "${app_vendor_path}/Zend" :
  ensure   => present,
  provider => git,
  source   => 'git://github.com/zendframework/zf1.git',
  require => File["${app_vendor_path}"],
}

file { "${app_path}/library/Zend" :
  ensure  => link,
  force   => true,
  target  => "${app_vendor_path}/Zend/library/Zend",
  require => [File["${app_path}/library"], Vcsrepo["${app_vendor_path}/Zend"]],
}

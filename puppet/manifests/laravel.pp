vcsrepo { "${app_vendor_path}/Laravel" :
  ensure   => present,
  provider => git,
  source   => 'git://github.com/laravel/laravel',
  require => File["${app_vendor_path}"],
}

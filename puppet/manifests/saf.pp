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
  require => [File["${app_path}/library"]],
}

file { '/var/www/html/info.php':
  ensure => file,
  mode  => '0755',
  source => '/vagrant/puppet/info.php',
  require => File['/var/www/html'],
}

vcsrepo { "${app_path}/${app}" :
  ensure   => present,
  force => true,
  provider => git,
  source   => 'git://github.com/jthurteau/saf-shell.git',
  require => File["${app_path}"],
}

file { "${doc_root}/saf" :
  ensure  => link,
  force   => true,
  target  => "${app_path}/${app}/public",
  require => [Vcsrepo["${app_path}/${app}"]],
}

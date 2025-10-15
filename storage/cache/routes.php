<?php

return array (
  'routes' => 
  array (
    0 => 
    array (
      'method' => 'GET',
      'path' => '/admin/ai',
      'original_path' => '/admin/ai',
      'handler' => 'admin/AI/Ai@index',
      'middleware' => 
      array (
      ),
      'name' => 'admin.ai.index',
      'params' => 
      array (
      ),
    ),
    1 => 
    array (
      'method' => 'GET',
      'path' => '/admin/ai/test',
      'original_path' => '/admin/ai/test',
      'handler' => 'admin/AI/Ai@test',
      'middleware' => 
      array (
      ),
      'name' => 'admin.ai.test',
      'params' => 
      array (
      ),
    ),
    2 => 
    array (
      'method' => 'POST',
      'path' => '/admin/ai/process',
      'original_path' => '/admin/ai/process',
      'handler' => 'admin/AI/Ai@process',
      'middleware' => 
      array (
      ),
      'name' => 'admin.ai.process',
      'params' => 
      array (
      ),
    ),
    3 => 
    array (
      'method' => 'GET',
      'path' => '/admin/ai/clearlogs',
      'original_path' => '/admin/ai/clearlogs',
      'handler' => 'admin/AI/Ai@clearlogs',
      'middleware' => 
      array (
      ),
      'name' => 'admin.ai.clearlogs',
      'params' => 
      array (
      ),
    ),
    4 => 
    array (
      'method' => 'GET',
      'path' => '/admin/automation',
      'original_path' => '/admin/automation',
      'handler' => 'admin/Automation/Automation@index',
      'middleware' => 
      array (
      ),
      'name' => 'admin.automation.index',
      'params' => 
      array (
      ),
    ),
    5 => 
    array (
      'method' => 'GET',
      'path' => '/admin/automation/clearcache',
      'original_path' => '/admin/automation/clearcache',
      'handler' => 'admin/Automation/Automation@clearcache',
      'middleware' => 
      array (
      ),
      'name' => 'admin.automation.clearcache',
      'params' => 
      array (
      ),
    ),
    6 => 
    array (
      'method' => 'GET',
      'path' => '/admin/automation/regenerateroutes',
      'original_path' => '/admin/automation/regenerateroutes',
      'handler' => 'admin/Automation/Automation@regenerateroutes',
      'middleware' => 
      array (
      ),
      'name' => 'admin.automation.regenerateroutes',
      'params' => 
      array (
      ),
    ),
    7 => 
    array (
      'method' => 'GET',
      'path' => '/admin/automation/scanmodules',
      'original_path' => '/admin/automation/scanmodules',
      'handler' => 'admin/Automation/Automation@scanmodules',
      'middleware' => 
      array (
      ),
      'name' => 'admin.automation.scanmodules',
      'params' => 
      array (
      ),
    ),
    8 => 
    array (
      'method' => 'GET',
      'path' => '/admin/blocks',
      'original_path' => '/admin/blocks',
      'handler' => 'admin/Blocks/Blocks@index',
      'middleware' => 
      array (
      ),
      'name' => 'admin.blocks.index',
      'params' => 
      array (
      ),
    ),
    9 => 
    array (
      'method' => 'GET',
      'path' => '/admin/blocks/create',
      'original_path' => '/admin/blocks/create',
      'handler' => 'admin/Blocks/Blocks@create',
      'middleware' => 
      array (
      ),
      'name' => 'admin.blocks.create',
      'params' => 
      array (
      ),
    ),
    10 => 
    array (
      'method' => 'POST',
      'path' => '/admin/blocks/store',
      'original_path' => '/admin/blocks/store',
      'handler' => 'admin/Blocks/Blocks@store',
      'middleware' => 
      array (
      ),
      'name' => 'admin.blocks.store',
      'params' => 
      array (
      ),
    ),
    11 => 
    array (
      'method' => 'GET',
      'path' => '/admin/blocks/edit',
      'original_path' => '/admin/blocks/edit',
      'handler' => 'admin/Blocks/Blocks@edit',
      'middleware' => 
      array (
      ),
      'name' => 'admin.blocks.edit',
      'params' => 
      array (
      ),
    ),
    12 => 
    array (
      'method' => 'POST',
      'path' => '/admin/blocks/update',
      'original_path' => '/admin/blocks/update',
      'handler' => 'admin/Blocks/Blocks@update',
      'middleware' => 
      array (
      ),
      'name' => 'admin.blocks.update',
      'params' => 
      array (
      ),
    ),
    13 => 
    array (
      'method' => 'GET',
      'path' => '/admin/blocks/delete',
      'original_path' => '/admin/blocks/delete',
      'handler' => 'admin/Blocks/Blocks@delete',
      'middleware' => 
      array (
      ),
      'name' => 'admin.blocks.delete',
      'params' => 
      array (
      ),
    ),
    14 => 
    array (
      'method' => 'GET',
      'path' => '/admin/blocks/toggle',
      'original_path' => '/admin/blocks/toggle',
      'handler' => 'admin/Blocks/Blocks@toggle',
      'middleware' => 
      array (
      ),
      'name' => 'admin.blocks.toggle',
      'params' => 
      array (
      ),
    ),
    15 => 
    array (
      'method' => 'GET',
      'path' => '/admin/blog/table',
      'original_path' => '/admin/blog/table',
      'handler' => 'admin/Blog/BlogTableController@index',
      'middleware' => 
      array (
      ),
      'name' => NULL,
      'params' => 
      array (
      ),
    ),
    16 => 
    array (
      'method' => 'POST',
      'path' => '/admin/blog/table/toggle',
      'original_path' => '/admin/blog/table/toggle',
      'handler' => 'admin/Blog/BlogTableController@toggleStatus',
      'middleware' => 
      array (
      ),
      'name' => NULL,
      'params' => 
      array (
      ),
    ),
    17 => 
    array (
      'method' => 'POST',
      'path' => '/admin/blog/table/bulk',
      'original_path' => '/admin/blog/table/bulk',
      'handler' => 'admin/Blog/BlogTableController@bulkAction',
      'middleware' => 
      array (
      ),
      'name' => NULL,
      'params' => 
      array (
      ),
    ),
    18 => 
    array (
      'method' => 'GET',
      'path' => '/admin/blog/table/json',
      'original_path' => '/admin/blog/table/json',
      'handler' => 'admin/Blog/BlogTableController@json',
      'middleware' => 
      array (
      ),
      'name' => NULL,
      'params' => 
      array (
      ),
    ),
    19 => 
    array (
      'method' => 'GET',
      'path' => '/admin/blog/table/export',
      'original_path' => '/admin/blog/table/export',
      'handler' => 'admin/Blog/BlogTableController@export',
      'middleware' => 
      array (
      ),
      'name' => NULL,
      'params' => 
      array (
      ),
    ),
    20 => 
    array (
      'method' => 'GET',
      'path' => '/admin/blog/form',
      'original_path' => '/admin/blog/form',
      'handler' => 'admin/Blog/BlogFormController@index',
      'middleware' => 
      array (
      ),
      'name' => NULL,
      'params' => 
      array (
      ),
    ),
    21 => 
    array (
      'method' => 'POST',
      'path' => '/admin/blog/form/save',
      'original_path' => '/admin/blog/form/save',
      'handler' => 'admin/Blog/BlogFormController@save',
      'middleware' => 
      array (
      ),
      'name' => NULL,
      'params' => 
      array (
      ),
    ),
    22 => 
    array (
      'method' => 'POST',
      'path' => '/admin/blog/delete',
      'original_path' => '/admin/blog/delete',
      'handler' => 'admin/Blog/BlogFormController@delete',
      'middleware' => 
      array (
      ),
      'name' => NULL,
      'params' => 
      array (
      ),
    ),
    23 => 
    array (
      'method' => 'GET',
      'path' => '/admin/blog/form/stats',
      'original_path' => '/admin/blog/form/stats',
      'handler' => 'admin/Blog/BlogFormController@stats',
      'middleware' => 
      array (
      ),
      'name' => NULL,
      'params' => 
      array (
      ),
    ),
    24 => 
    array (
      'method' => 'GET',
      'path' => '/admin',
      'original_path' => '/admin',
      'handler' => 'admin/Dashboard/Dashboard@index',
      'middleware' => 
      array (
      ),
      'name' => 'admin.dashboard',
      'params' => 
      array (
      ),
    ),
    25 => 
    array (
      'method' => 'GET',
      'path' => '/admin/dashboard',
      'original_path' => '/admin/dashboard',
      'handler' => 'admin/Dashboard/Dashboard@index',
      'middleware' => 
      array (
      ),
      'name' => 'admin.dashboard.index',
      'params' => 
      array (
      ),
    ),
    26 => 
    array (
      'method' => 'GET',
      'path' => '/admin/analytics',
      'original_path' => '/admin/analytics',
      'handler' => 'admin/Dashboard/Dashboard@analytics',
      'middleware' => 
      array (
      ),
      'name' => 'admin.analytics',
      'params' => 
      array (
      ),
    ),
    27 => 
    array (
      'method' => 'GET',
      'path' => '/admin',
      'original_path' => '/admin',
      'handler' => 'admin/Home/AdminHeaderController@index',
      'middleware' => 
      array (
      ),
      'name' => 'admin.home',
      'params' => 
      array (
      ),
    ),
    28 => 
    array (
      'method' => 'GET',
      'path' => '/admin/footer',
      'original_path' => '/admin/footer',
      'handler' => 'admin/Home/AdminFooterController@index',
      'middleware' => 
      array (
      ),
      'name' => 'admin.footer',
      'params' => 
      array (
      ),
    ),
    29 => 
    array (
      'method' => 'GET',
      'path' => '/admin/module-generator',
      'original_path' => '/admin/module-generator',
      'handler' => 'admin/ModuleGenerator/Modulegenerator@index',
      'middleware' => 
      array (
      ),
      'name' => 'admin.modulegenerator.index',
      'params' => 
      array (
      ),
    ),
    30 => 
    array (
      'method' => 'POST',
      'path' => '/admin/module-generator/generate',
      'original_path' => '/admin/module-generator/generate',
      'handler' => 'admin/ModuleGenerator/Modulegenerator@generate',
      'middleware' => 
      array (
      ),
      'name' => 'admin.modulegenerator.generate',
      'params' => 
      array (
      ),
    ),
    31 => 
    array (
      'method' => 'GET',
      'path' => '/admin/pages',
      'original_path' => '/admin/pages',
      'handler' => 'admin/Pages/Pages@index',
      'middleware' => 
      array (
      ),
      'name' => 'admin.pages.index',
      'params' => 
      array (
      ),
    ),
    32 => 
    array (
      'method' => 'GET',
      'path' => '/admin/pages/create',
      'original_path' => '/admin/pages/create',
      'handler' => 'admin/Pages/Pages@create',
      'middleware' => 
      array (
      ),
      'name' => 'admin.pages.create',
      'params' => 
      array (
      ),
    ),
    33 => 
    array (
      'method' => 'POST',
      'path' => '/admin/pages/store',
      'original_path' => '/admin/pages/store',
      'handler' => 'admin/Pages/Pages@store',
      'middleware' => 
      array (
      ),
      'name' => 'admin.pages.store',
      'params' => 
      array (
      ),
    ),
    34 => 
    array (
      'method' => 'GET',
      'path' => '/admin/pages/edit',
      'original_path' => '/admin/pages/edit',
      'handler' => 'admin/Pages/Pages@edit',
      'middleware' => 
      array (
      ),
      'name' => 'admin.pages.edit',
      'params' => 
      array (
      ),
    ),
    35 => 
    array (
      'method' => 'POST',
      'path' => '/admin/pages/update',
      'original_path' => '/admin/pages/update',
      'handler' => 'admin/Pages/Pages@update',
      'middleware' => 
      array (
      ),
      'name' => 'admin.pages.update',
      'params' => 
      array (
      ),
    ),
    36 => 
    array (
      'method' => 'GET',
      'path' => '/admin/pages/delete',
      'original_path' => '/admin/pages/delete',
      'handler' => 'admin/Pages/Pages@delete',
      'middleware' => 
      array (
      ),
      'name' => 'admin.pages.delete',
      'params' => 
      array (
      ),
    ),
    37 => 
    array (
      'method' => 'GET',
      'path' => '/admin/pages/toggle',
      'original_path' => '/admin/pages/toggle',
      'handler' => 'admin/Pages/Pages@toggle',
      'middleware' => 
      array (
      ),
      'name' => 'admin.pages.toggle',
      'params' => 
      array (
      ),
    ),
    38 => 
    array (
      'method' => 'GET',
      'path' => '/admin/public-content',
      'original_path' => '/admin/public-content',
      'handler' => 'admin/PublicContent/Publiccontent@index',
      'middleware' => 
      array (
      ),
      'name' => 'admin.publiccontent.index',
      'params' => 
      array (
      ),
    ),
    39 => 
    array (
      'method' => 'GET',
      'path' => '/admin/public-content/settings',
      'original_path' => '/admin/public-content/settings',
      'handler' => 'admin/PublicContent/Publiccontent@settings',
      'middleware' => 
      array (
      ),
      'name' => 'admin.publiccontent.settings',
      'params' => 
      array (
      ),
    ),
    40 => 
    array (
      'method' => 'POST',
      'path' => '/admin/public-content/savesettings',
      'original_path' => '/admin/public-content/savesettings',
      'handler' => 'admin/PublicContent/Publiccontent@savesettings',
      'middleware' => 
      array (
      ),
      'name' => 'admin.publiccontent.savesettings',
      'params' => 
      array (
      ),
    ),
    41 => 
    array (
      'method' => 'GET',
      'path' => '/admin/roles',
      'original_path' => '/admin/roles',
      'handler' => 'admin/Roles/Roles@index',
      'middleware' => 
      array (
      ),
      'name' => 'admin.roles.index',
      'params' => 
      array (
      ),
    ),
    42 => 
    array (
      'method' => 'GET',
      'path' => '/admin/roles/create',
      'original_path' => '/admin/roles/create',
      'handler' => 'admin/Roles/Roles@create',
      'middleware' => 
      array (
      ),
      'name' => 'admin.roles.create',
      'params' => 
      array (
      ),
    ),
    43 => 
    array (
      'method' => 'POST',
      'path' => '/admin/roles/store',
      'original_path' => '/admin/roles/store',
      'handler' => 'admin/Roles/Roles@store',
      'middleware' => 
      array (
      ),
      'name' => 'admin.roles.store',
      'params' => 
      array (
      ),
    ),
    44 => 
    array (
      'method' => 'GET',
      'path' => '/admin/roles/edit',
      'original_path' => '/admin/roles/edit',
      'handler' => 'admin/Roles/Roles@edit',
      'middleware' => 
      array (
      ),
      'name' => 'admin.roles.edit',
      'params' => 
      array (
      ),
    ),
    45 => 
    array (
      'method' => 'POST',
      'path' => '/admin/roles/update',
      'original_path' => '/admin/roles/update',
      'handler' => 'admin/Roles/Roles@update',
      'middleware' => 
      array (
      ),
      'name' => 'admin.roles.update',
      'params' => 
      array (
      ),
    ),
    46 => 
    array (
      'method' => 'GET',
      'path' => '/admin/roles/delete',
      'original_path' => '/admin/roles/delete',
      'handler' => 'admin/Roles/Roles@delete',
      'middleware' => 
      array (
      ),
      'name' => 'admin.roles.delete',
      'params' => 
      array (
      ),
    ),
    47 => 
    array (
      'method' => 'GET',
      'path' => '/admin/roles/toggle',
      'original_path' => '/admin/roles/toggle',
      'handler' => 'admin/Roles/Roles@toggle',
      'middleware' => 
      array (
      ),
      'name' => 'admin.roles.toggle',
      'params' => 
      array (
      ),
    ),
    48 => 
    array (
      'method' => 'GET',
      'path' => '/admin/users',
      'original_path' => '/admin/users',
      'handler' => 'admin/Users/Users@index',
      'middleware' => 
      array (
      ),
      'name' => 'admin.users.index',
      'params' => 
      array (
      ),
    ),
    49 => 
    array (
      'method' => 'GET',
      'path' => '/admin/users/create',
      'original_path' => '/admin/users/create',
      'handler' => 'admin/Users/Users@create',
      'middleware' => 
      array (
      ),
      'name' => 'admin.users.create',
      'params' => 
      array (
      ),
    ),
    50 => 
    array (
      'method' => 'POST',
      'path' => '/admin/users/store',
      'original_path' => '/admin/users/store',
      'handler' => 'admin/Users/Users@store',
      'middleware' => 
      array (
      ),
      'name' => 'admin.users.store',
      'params' => 
      array (
      ),
    ),
    51 => 
    array (
      'method' => 'GET',
      'path' => '/admin/users/edit',
      'original_path' => '/admin/users/edit',
      'handler' => 'admin/Users/Users@edit',
      'middleware' => 
      array (
      ),
      'name' => 'admin.users.edit',
      'params' => 
      array (
      ),
    ),
    52 => 
    array (
      'method' => 'POST',
      'path' => '/admin/users/update',
      'original_path' => '/admin/users/update',
      'handler' => 'admin/Users/Users@update',
      'middleware' => 
      array (
      ),
      'name' => 'admin.users.update',
      'params' => 
      array (
      ),
    ),
    53 => 
    array (
      'method' => 'GET',
      'path' => '/admin/users/delete',
      'original_path' => '/admin/users/delete',
      'handler' => 'admin/Users/Users@delete',
      'middleware' => 
      array (
      ),
      'name' => 'admin.users.delete',
      'params' => 
      array (
      ),
    ),
    54 => 
    array (
      'method' => 'GET',
      'path' => '/admin/users/toggle',
      'original_path' => '/admin/users/toggle',
      'handler' => 'admin/Users/Users@toggle',
      'middleware' => 
      array (
      ),
      'name' => 'admin.users.toggle',
      'params' => 
      array (
      ),
    ),
    55 => 
    array (
      'method' => 'GET',
      'path' => '/admin/users/view',
      'original_path' => '/admin/users/view',
      'handler' => 'admin/Users/Users@view',
      'middleware' => 
      array (
      ),
      'name' => 'admin.users.view',
      'params' => 
      array (
      ),
    ),
    56 => 
    array (
      'method' => 'GET',
      'path' => '/about',
      'original_path' => '/about',
      'handler' => 'public/About/About@index',
      'middleware' => 
      array (
      ),
      'name' => 'about',
      'params' => 
      array (
      ),
    ),
    57 => 
    array (
      'method' => 'GET',
      'path' => '/about/team',
      'original_path' => '/about/team',
      'handler' => 'public/About/About@team',
      'middleware' => 
      array (
      ),
      'name' => 'about.team',
      'params' => 
      array (
      ),
    ),
    58 => 
    array (
      'method' => 'GET',
      'path' => '/about/contact',
      'original_path' => '/about/contact',
      'handler' => 'public/About/About@contact',
      'middleware' => 
      array (
      ),
      'name' => 'about.contact',
      'params' => 
      array (
      ),
    ),
    59 => 
    array (
      'method' => 'POST',
      'path' => '/about/contact',
      'original_path' => '/about/contact',
      'handler' => 'public/About/About@contact',
      'middleware' => 
      array (
      ),
      'name' => 'about.contact.post',
      'params' => 
      array (
      ),
    ),
    60 => 
    array (
      'method' => 'GET',
      'path' => '/login',
      'original_path' => '/login',
      'handler' => 'public/Auth/AuthController@login',
      'middleware' => 
      array (
      ),
      'name' => 'auth.login',
      'params' => 
      array (
      ),
    ),
    61 => 
    array (
      'method' => 'POST',
      'path' => '/login',
      'original_path' => '/login',
      'handler' => 'public/Auth/AuthController@login',
      'middleware' => 
      array (
      ),
      'name' => 'auth.login.post',
      'params' => 
      array (
      ),
    ),
    62 => 
    array (
      'method' => 'GET',
      'path' => '/register',
      'original_path' => '/register',
      'handler' => 'public/Auth/AuthController@register',
      'middleware' => 
      array (
      ),
      'name' => 'auth.register',
      'params' => 
      array (
      ),
    ),
    63 => 
    array (
      'method' => 'POST',
      'path' => '/register',
      'original_path' => '/register',
      'handler' => 'public/Auth/AuthController@register',
      'middleware' => 
      array (
      ),
      'name' => 'auth.register.post',
      'params' => 
      array (
      ),
    ),
    64 => 
    array (
      'method' => 'GET',
      'path' => '/logout',
      'original_path' => '/logout',
      'handler' => 'public/Auth/AuthController@logout',
      'middleware' => 
      array (
      ),
      'name' => 'auth.logout',
      'params' => 
      array (
      ),
    ),
    65 => 
    array (
      'method' => 'GET',
      'path' => '/forgot-password',
      'original_path' => '/forgot-password',
      'handler' => 'public/Auth/AuthController@forgotPassword',
      'middleware' => 
      array (
      ),
      'name' => 'auth.forgot',
      'params' => 
      array (
      ),
    ),
    66 => 
    array (
      'method' => 'POST',
      'path' => '/forgot-password',
      'original_path' => '/forgot-password',
      'handler' => 'public/Auth/AuthController@forgotPassword',
      'middleware' => 
      array (
      ),
      'name' => 'auth.forgot.post',
      'params' => 
      array (
      ),
    ),
    67 => 
    array (
      'method' => 'GET',
      'path' => '/blog',
      'original_path' => '/blog',
      'handler' => 'public/Blog/Blog@index',
      'middleware' => 
      array (
      ),
      'name' => 'blog.index',
      'params' => 
      array (
      ),
    ),
    68 => 
    array (
      'method' => 'GET',
      'path' => '/blog/{id:\\d+}',
      'original_path' => '/blog/{id:\\d+}',
      'handler' => 'public/Blog/Blog@show',
      'middleware' => 
      array (
      ),
      'name' => 'blog.show',
      'params' => 
      array (
      ),
    ),
    69 => 
    array (
      'method' => 'GET',
      'path' => '/blog/category/{category}',
      'original_path' => '/blog/category/{category}',
      'handler' => 'public/Blog/Blog@category',
      'middleware' => 
      array (
      ),
      'name' => 'blog.category',
      'params' => 
      array (
      ),
    ),
    70 => 
    array (
      'method' => 'GET',
      'path' => '/header',
      'original_path' => '/header',
      'handler' => 'public/Common/Header@index',
      'middleware' => 
      array (
      ),
      'name' => 'header',
      'params' => 
      array (
      ),
    ),
    71 => 
    array (
      'method' => 'GET',
      'path' => '/footer',
      'original_path' => '/footer',
      'handler' => 'public/Common/Footer@index',
      'middleware' => 
      array (
      ),
      'name' => 'footer',
      'params' => 
      array (
      ),
    ),
    72 => 
    array (
      'method' => 'GET',
      'path' => '/',
      'original_path' => '/',
      'handler' => 'public/Home/Home@index',
      'middleware' => 
      array (
      ),
      'name' => 'home',
      'params' => 
      array (
      ),
    ),
    73 => 
    array (
      'method' => 'GET',
      'path' => '/get-started',
      'original_path' => '/get-started',
      'handler' => 'public/Home/Home@getStarted',
      'middleware' => 
      array (
      ),
      'name' => 'home.get-started',
      'params' => 
      array (
      ),
    ),
    74 => 
    array (
      'method' => 'GET',
      'path' => '/automate',
      'original_path' => '/automate',
      'handler' => 'automate/Generator/Generator@index',
      'middleware' => 
      array (
      ),
      'name' => 'automate.index',
      'params' => 
      array (
      ),
    ),
    75 => 
    array (
      'method' => 'POST',
      'path' => '/automate/generate',
      'original_path' => '/automate/generate',
      'handler' => 'automate/Generator/Generator@generate',
      'middleware' => 
      array (
      ),
      'name' => 'automate.generate',
      'params' => 
      array (
      ),
    ),
    76 => 
    array (
      'method' => 'GET',
      'path' => '/automate/list',
      'original_path' => '/automate/list',
      'handler' => 'automate/Generator/Generator@list',
      'middleware' => 
      array (
      ),
      'name' => 'automate.list',
      'params' => 
      array (
      ),
    ),
    77 => 
    array (
      'method' => 'GET',
      'path' => '/admin/automate/addcontroller',
      'original_path' => '/admin/automate/addcontroller',
      'handler' => 'admin/automate/addcontroller@index',
      'middleware' => 
      array (
      ),
      'name' => NULL,
      'params' => 
      array (
      ),
    ),
    78 => 
    array (
      'method' => 'POST',
      'path' => '/admin/automate/addcontroller',
      'original_path' => '/admin/automate/addcontroller',
      'handler' => 'admin/automate/addcontroller@generate',
      'middleware' => 
      array (
      ),
      'name' => NULL,
      'params' => 
      array (
      ),
    ),
    79 => 
    array (
      'method' => 'GET',
      'path' => '/admin/automate/addmodels',
      'original_path' => '/admin/automate/addmodels',
      'handler' => 'admin/automate/addmodels@index',
      'middleware' => 
      array (
      ),
      'name' => NULL,
      'params' => 
      array (
      ),
    ),
    80 => 
    array (
      'method' => 'POST',
      'path' => '/admin/automate/addmodels',
      'original_path' => '/admin/automate/addmodels',
      'handler' => 'admin/automate/addmodels@generate',
      'middleware' => 
      array (
      ),
      'name' => NULL,
      'params' => 
      array (
      ),
    ),
    81 => 
    array (
      'method' => 'GET',
      'path' => '/admin/automate/addmodules',
      'original_path' => '/admin/automate/addmodules',
      'handler' => 'admin/automate/addmodules@index',
      'middleware' => 
      array (
      ),
      'name' => NULL,
      'params' => 
      array (
      ),
    ),
    82 => 
    array (
      'method' => 'POST',
      'path' => '/admin/automate/addmodules',
      'original_path' => '/admin/automate/addmodules',
      'handler' => 'admin/automate/addmodules@generate',
      'middleware' => 
      array (
      ),
      'name' => NULL,
      'params' => 
      array (
      ),
    ),
    83 => 
    array (
      'method' => 'GET',
      'path' => '/admin/automate/addview',
      'original_path' => '/admin/automate/addview',
      'handler' => 'admin/automate/addview@index',
      'middleware' => 
      array (
      ),
      'name' => NULL,
      'params' => 
      array (
      ),
    ),
    84 => 
    array (
      'method' => 'POST',
      'path' => '/admin/automate/addview',
      'original_path' => '/admin/automate/addview',
      'handler' => 'admin/automate/addview@generate',
      'middleware' => 
      array (
      ),
      'name' => NULL,
      'params' => 
      array (
      ),
    ),
    85 => 
    array (
      'method' => 'GET',
      'path' => '/public/automate/addcontroller',
      'original_path' => '/public/automate/addcontroller',
      'handler' => 'public/automate/addcontroller@index',
      'middleware' => 
      array (
      ),
      'name' => NULL,
      'params' => 
      array (
      ),
    ),
    86 => 
    array (
      'method' => 'POST',
      'path' => '/public/automate/addcontroller',
      'original_path' => '/public/automate/addcontroller',
      'handler' => 'public/automate/addcontroller@generate',
      'middleware' => 
      array (
      ),
      'name' => NULL,
      'params' => 
      array (
      ),
    ),
    87 => 
    array (
      'method' => 'GET',
      'path' => '/public/automate/addmodels',
      'original_path' => '/public/automate/addmodels',
      'handler' => 'public/automate/addmodels@index',
      'middleware' => 
      array (
      ),
      'name' => NULL,
      'params' => 
      array (
      ),
    ),
    88 => 
    array (
      'method' => 'POST',
      'path' => '/public/automate/addmodels',
      'original_path' => '/public/automate/addmodels',
      'handler' => 'public/automate/addmodels@generate',
      'middleware' => 
      array (
      ),
      'name' => NULL,
      'params' => 
      array (
      ),
    ),
    89 => 
    array (
      'method' => 'GET',
      'path' => '/public/automate/addmodules',
      'original_path' => '/public/automate/addmodules',
      'handler' => 'public/automate/addmodules@index',
      'middleware' => 
      array (
      ),
      'name' => NULL,
      'params' => 
      array (
      ),
    ),
    90 => 
    array (
      'method' => 'POST',
      'path' => '/public/automate/addmodules',
      'original_path' => '/public/automate/addmodules',
      'handler' => 'public/automate/addmodules@generate',
      'middleware' => 
      array (
      ),
      'name' => NULL,
      'params' => 
      array (
      ),
    ),
    91 => 
    array (
      'method' => 'GET',
      'path' => '/public/automate/addview',
      'original_path' => '/public/automate/addview',
      'handler' => 'public/automate/addview@index',
      'middleware' => 
      array (
      ),
      'name' => NULL,
      'params' => 
      array (
      ),
    ),
    92 => 
    array (
      'method' => 'POST',
      'path' => '/public/automate/addview',
      'original_path' => '/public/automate/addview',
      'handler' => 'public/automate/addview@generate',
      'middleware' => 
      array (
      ),
      'name' => NULL,
      'params' => 
      array (
      ),
    ),
  ),
  'named' => 
  array (
    'admin.ai.index' => 
    array (
      'method' => 'GET',
      'path' => '/admin/ai',
      'original_path' => '/admin/ai',
      'handler' => 'admin/AI/Ai@index',
      'middleware' => 
      array (
      ),
      'name' => 'admin.ai.index',
      'params' => 
      array (
      ),
    ),
    'admin.ai.test' => 
    array (
      'method' => 'GET',
      'path' => '/admin/ai/test',
      'original_path' => '/admin/ai/test',
      'handler' => 'admin/AI/Ai@test',
      'middleware' => 
      array (
      ),
      'name' => 'admin.ai.test',
      'params' => 
      array (
      ),
    ),
    'admin.ai.process' => 
    array (
      'method' => 'POST',
      'path' => '/admin/ai/process',
      'original_path' => '/admin/ai/process',
      'handler' => 'admin/AI/Ai@process',
      'middleware' => 
      array (
      ),
      'name' => 'admin.ai.process',
      'params' => 
      array (
      ),
    ),
    'admin.ai.clearlogs' => 
    array (
      'method' => 'GET',
      'path' => '/admin/ai/clearlogs',
      'original_path' => '/admin/ai/clearlogs',
      'handler' => 'admin/AI/Ai@clearlogs',
      'middleware' => 
      array (
      ),
      'name' => 'admin.ai.clearlogs',
      'params' => 
      array (
      ),
    ),
    'admin.automation.index' => 
    array (
      'method' => 'GET',
      'path' => '/admin/automation',
      'original_path' => '/admin/automation',
      'handler' => 'admin/Automation/Automation@index',
      'middleware' => 
      array (
      ),
      'name' => 'admin.automation.index',
      'params' => 
      array (
      ),
    ),
    'admin.automation.clearcache' => 
    array (
      'method' => 'GET',
      'path' => '/admin/automation/clearcache',
      'original_path' => '/admin/automation/clearcache',
      'handler' => 'admin/Automation/Automation@clearcache',
      'middleware' => 
      array (
      ),
      'name' => 'admin.automation.clearcache',
      'params' => 
      array (
      ),
    ),
    'admin.automation.regenerateroutes' => 
    array (
      'method' => 'GET',
      'path' => '/admin/automation/regenerateroutes',
      'original_path' => '/admin/automation/regenerateroutes',
      'handler' => 'admin/Automation/Automation@regenerateroutes',
      'middleware' => 
      array (
      ),
      'name' => 'admin.automation.regenerateroutes',
      'params' => 
      array (
      ),
    ),
    'admin.automation.scanmodules' => 
    array (
      'method' => 'GET',
      'path' => '/admin/automation/scanmodules',
      'original_path' => '/admin/automation/scanmodules',
      'handler' => 'admin/Automation/Automation@scanmodules',
      'middleware' => 
      array (
      ),
      'name' => 'admin.automation.scanmodules',
      'params' => 
      array (
      ),
    ),
    'admin.blocks.index' => 
    array (
      'method' => 'GET',
      'path' => '/admin/blocks',
      'original_path' => '/admin/blocks',
      'handler' => 'admin/Blocks/Blocks@index',
      'middleware' => 
      array (
      ),
      'name' => 'admin.blocks.index',
      'params' => 
      array (
      ),
    ),
    'admin.blocks.create' => 
    array (
      'method' => 'GET',
      'path' => '/admin/blocks/create',
      'original_path' => '/admin/blocks/create',
      'handler' => 'admin/Blocks/Blocks@create',
      'middleware' => 
      array (
      ),
      'name' => 'admin.blocks.create',
      'params' => 
      array (
      ),
    ),
    'admin.blocks.store' => 
    array (
      'method' => 'POST',
      'path' => '/admin/blocks/store',
      'original_path' => '/admin/blocks/store',
      'handler' => 'admin/Blocks/Blocks@store',
      'middleware' => 
      array (
      ),
      'name' => 'admin.blocks.store',
      'params' => 
      array (
      ),
    ),
    'admin.blocks.edit' => 
    array (
      'method' => 'GET',
      'path' => '/admin/blocks/edit',
      'original_path' => '/admin/blocks/edit',
      'handler' => 'admin/Blocks/Blocks@edit',
      'middleware' => 
      array (
      ),
      'name' => 'admin.blocks.edit',
      'params' => 
      array (
      ),
    ),
    'admin.blocks.update' => 
    array (
      'method' => 'POST',
      'path' => '/admin/blocks/update',
      'original_path' => '/admin/blocks/update',
      'handler' => 'admin/Blocks/Blocks@update',
      'middleware' => 
      array (
      ),
      'name' => 'admin.blocks.update',
      'params' => 
      array (
      ),
    ),
    'admin.blocks.delete' => 
    array (
      'method' => 'GET',
      'path' => '/admin/blocks/delete',
      'original_path' => '/admin/blocks/delete',
      'handler' => 'admin/Blocks/Blocks@delete',
      'middleware' => 
      array (
      ),
      'name' => 'admin.blocks.delete',
      'params' => 
      array (
      ),
    ),
    'admin.blocks.toggle' => 
    array (
      'method' => 'GET',
      'path' => '/admin/blocks/toggle',
      'original_path' => '/admin/blocks/toggle',
      'handler' => 'admin/Blocks/Blocks@toggle',
      'middleware' => 
      array (
      ),
      'name' => 'admin.blocks.toggle',
      'params' => 
      array (
      ),
    ),
    'admin.dashboard' => 
    array (
      'method' => 'GET',
      'path' => '/admin',
      'original_path' => '/admin',
      'handler' => 'admin/Dashboard/Dashboard@index',
      'middleware' => 
      array (
      ),
      'name' => 'admin.dashboard',
      'params' => 
      array (
      ),
    ),
    'admin.dashboard.index' => 
    array (
      'method' => 'GET',
      'path' => '/admin/dashboard',
      'original_path' => '/admin/dashboard',
      'handler' => 'admin/Dashboard/Dashboard@index',
      'middleware' => 
      array (
      ),
      'name' => 'admin.dashboard.index',
      'params' => 
      array (
      ),
    ),
    'admin.analytics' => 
    array (
      'method' => 'GET',
      'path' => '/admin/analytics',
      'original_path' => '/admin/analytics',
      'handler' => 'admin/Dashboard/Dashboard@analytics',
      'middleware' => 
      array (
      ),
      'name' => 'admin.analytics',
      'params' => 
      array (
      ),
    ),
    'admin.home' => 
    array (
      'method' => 'GET',
      'path' => '/admin',
      'original_path' => '/admin',
      'handler' => 'admin/Home/AdminHeaderController@index',
      'middleware' => 
      array (
      ),
      'name' => 'admin.home',
      'params' => 
      array (
      ),
    ),
    'admin.footer' => 
    array (
      'method' => 'GET',
      'path' => '/admin/footer',
      'original_path' => '/admin/footer',
      'handler' => 'admin/Home/AdminFooterController@index',
      'middleware' => 
      array (
      ),
      'name' => 'admin.footer',
      'params' => 
      array (
      ),
    ),
    'admin.modulegenerator.index' => 
    array (
      'method' => 'GET',
      'path' => '/admin/module-generator',
      'original_path' => '/admin/module-generator',
      'handler' => 'admin/ModuleGenerator/Modulegenerator@index',
      'middleware' => 
      array (
      ),
      'name' => 'admin.modulegenerator.index',
      'params' => 
      array (
      ),
    ),
    'admin.modulegenerator.generate' => 
    array (
      'method' => 'POST',
      'path' => '/admin/module-generator/generate',
      'original_path' => '/admin/module-generator/generate',
      'handler' => 'admin/ModuleGenerator/Modulegenerator@generate',
      'middleware' => 
      array (
      ),
      'name' => 'admin.modulegenerator.generate',
      'params' => 
      array (
      ),
    ),
    'admin.pages.index' => 
    array (
      'method' => 'GET',
      'path' => '/admin/pages',
      'original_path' => '/admin/pages',
      'handler' => 'admin/Pages/Pages@index',
      'middleware' => 
      array (
      ),
      'name' => 'admin.pages.index',
      'params' => 
      array (
      ),
    ),
    'admin.pages.create' => 
    array (
      'method' => 'GET',
      'path' => '/admin/pages/create',
      'original_path' => '/admin/pages/create',
      'handler' => 'admin/Pages/Pages@create',
      'middleware' => 
      array (
      ),
      'name' => 'admin.pages.create',
      'params' => 
      array (
      ),
    ),
    'admin.pages.store' => 
    array (
      'method' => 'POST',
      'path' => '/admin/pages/store',
      'original_path' => '/admin/pages/store',
      'handler' => 'admin/Pages/Pages@store',
      'middleware' => 
      array (
      ),
      'name' => 'admin.pages.store',
      'params' => 
      array (
      ),
    ),
    'admin.pages.edit' => 
    array (
      'method' => 'GET',
      'path' => '/admin/pages/edit',
      'original_path' => '/admin/pages/edit',
      'handler' => 'admin/Pages/Pages@edit',
      'middleware' => 
      array (
      ),
      'name' => 'admin.pages.edit',
      'params' => 
      array (
      ),
    ),
    'admin.pages.update' => 
    array (
      'method' => 'POST',
      'path' => '/admin/pages/update',
      'original_path' => '/admin/pages/update',
      'handler' => 'admin/Pages/Pages@update',
      'middleware' => 
      array (
      ),
      'name' => 'admin.pages.update',
      'params' => 
      array (
      ),
    ),
    'admin.pages.delete' => 
    array (
      'method' => 'GET',
      'path' => '/admin/pages/delete',
      'original_path' => '/admin/pages/delete',
      'handler' => 'admin/Pages/Pages@delete',
      'middleware' => 
      array (
      ),
      'name' => 'admin.pages.delete',
      'params' => 
      array (
      ),
    ),
    'admin.pages.toggle' => 
    array (
      'method' => 'GET',
      'path' => '/admin/pages/toggle',
      'original_path' => '/admin/pages/toggle',
      'handler' => 'admin/Pages/Pages@toggle',
      'middleware' => 
      array (
      ),
      'name' => 'admin.pages.toggle',
      'params' => 
      array (
      ),
    ),
    'admin.publiccontent.index' => 
    array (
      'method' => 'GET',
      'path' => '/admin/public-content',
      'original_path' => '/admin/public-content',
      'handler' => 'admin/PublicContent/Publiccontent@index',
      'middleware' => 
      array (
      ),
      'name' => 'admin.publiccontent.index',
      'params' => 
      array (
      ),
    ),
    'admin.publiccontent.settings' => 
    array (
      'method' => 'GET',
      'path' => '/admin/public-content/settings',
      'original_path' => '/admin/public-content/settings',
      'handler' => 'admin/PublicContent/Publiccontent@settings',
      'middleware' => 
      array (
      ),
      'name' => 'admin.publiccontent.settings',
      'params' => 
      array (
      ),
    ),
    'admin.publiccontent.savesettings' => 
    array (
      'method' => 'POST',
      'path' => '/admin/public-content/savesettings',
      'original_path' => '/admin/public-content/savesettings',
      'handler' => 'admin/PublicContent/Publiccontent@savesettings',
      'middleware' => 
      array (
      ),
      'name' => 'admin.publiccontent.savesettings',
      'params' => 
      array (
      ),
    ),
    'admin.roles.index' => 
    array (
      'method' => 'GET',
      'path' => '/admin/roles',
      'original_path' => '/admin/roles',
      'handler' => 'admin/Roles/Roles@index',
      'middleware' => 
      array (
      ),
      'name' => 'admin.roles.index',
      'params' => 
      array (
      ),
    ),
    'admin.roles.create' => 
    array (
      'method' => 'GET',
      'path' => '/admin/roles/create',
      'original_path' => '/admin/roles/create',
      'handler' => 'admin/Roles/Roles@create',
      'middleware' => 
      array (
      ),
      'name' => 'admin.roles.create',
      'params' => 
      array (
      ),
    ),
    'admin.roles.store' => 
    array (
      'method' => 'POST',
      'path' => '/admin/roles/store',
      'original_path' => '/admin/roles/store',
      'handler' => 'admin/Roles/Roles@store',
      'middleware' => 
      array (
      ),
      'name' => 'admin.roles.store',
      'params' => 
      array (
      ),
    ),
    'admin.roles.edit' => 
    array (
      'method' => 'GET',
      'path' => '/admin/roles/edit',
      'original_path' => '/admin/roles/edit',
      'handler' => 'admin/Roles/Roles@edit',
      'middleware' => 
      array (
      ),
      'name' => 'admin.roles.edit',
      'params' => 
      array (
      ),
    ),
    'admin.roles.update' => 
    array (
      'method' => 'POST',
      'path' => '/admin/roles/update',
      'original_path' => '/admin/roles/update',
      'handler' => 'admin/Roles/Roles@update',
      'middleware' => 
      array (
      ),
      'name' => 'admin.roles.update',
      'params' => 
      array (
      ),
    ),
    'admin.roles.delete' => 
    array (
      'method' => 'GET',
      'path' => '/admin/roles/delete',
      'original_path' => '/admin/roles/delete',
      'handler' => 'admin/Roles/Roles@delete',
      'middleware' => 
      array (
      ),
      'name' => 'admin.roles.delete',
      'params' => 
      array (
      ),
    ),
    'admin.roles.toggle' => 
    array (
      'method' => 'GET',
      'path' => '/admin/roles/toggle',
      'original_path' => '/admin/roles/toggle',
      'handler' => 'admin/Roles/Roles@toggle',
      'middleware' => 
      array (
      ),
      'name' => 'admin.roles.toggle',
      'params' => 
      array (
      ),
    ),
    'admin.users.index' => 
    array (
      'method' => 'GET',
      'path' => '/admin/users',
      'original_path' => '/admin/users',
      'handler' => 'admin/Users/Users@index',
      'middleware' => 
      array (
      ),
      'name' => 'admin.users.index',
      'params' => 
      array (
      ),
    ),
    'admin.users.create' => 
    array (
      'method' => 'GET',
      'path' => '/admin/users/create',
      'original_path' => '/admin/users/create',
      'handler' => 'admin/Users/Users@create',
      'middleware' => 
      array (
      ),
      'name' => 'admin.users.create',
      'params' => 
      array (
      ),
    ),
    'admin.users.store' => 
    array (
      'method' => 'POST',
      'path' => '/admin/users/store',
      'original_path' => '/admin/users/store',
      'handler' => 'admin/Users/Users@store',
      'middleware' => 
      array (
      ),
      'name' => 'admin.users.store',
      'params' => 
      array (
      ),
    ),
    'admin.users.edit' => 
    array (
      'method' => 'GET',
      'path' => '/admin/users/edit',
      'original_path' => '/admin/users/edit',
      'handler' => 'admin/Users/Users@edit',
      'middleware' => 
      array (
      ),
      'name' => 'admin.users.edit',
      'params' => 
      array (
      ),
    ),
    'admin.users.update' => 
    array (
      'method' => 'POST',
      'path' => '/admin/users/update',
      'original_path' => '/admin/users/update',
      'handler' => 'admin/Users/Users@update',
      'middleware' => 
      array (
      ),
      'name' => 'admin.users.update',
      'params' => 
      array (
      ),
    ),
    'admin.users.delete' => 
    array (
      'method' => 'GET',
      'path' => '/admin/users/delete',
      'original_path' => '/admin/users/delete',
      'handler' => 'admin/Users/Users@delete',
      'middleware' => 
      array (
      ),
      'name' => 'admin.users.delete',
      'params' => 
      array (
      ),
    ),
    'admin.users.toggle' => 
    array (
      'method' => 'GET',
      'path' => '/admin/users/toggle',
      'original_path' => '/admin/users/toggle',
      'handler' => 'admin/Users/Users@toggle',
      'middleware' => 
      array (
      ),
      'name' => 'admin.users.toggle',
      'params' => 
      array (
      ),
    ),
    'admin.users.view' => 
    array (
      'method' => 'GET',
      'path' => '/admin/users/view',
      'original_path' => '/admin/users/view',
      'handler' => 'admin/Users/Users@view',
      'middleware' => 
      array (
      ),
      'name' => 'admin.users.view',
      'params' => 
      array (
      ),
    ),
    'about' => 
    array (
      'method' => 'GET',
      'path' => '/about',
      'original_path' => '/about',
      'handler' => 'public/About/About@index',
      'middleware' => 
      array (
      ),
      'name' => 'about',
      'params' => 
      array (
      ),
    ),
    'about.team' => 
    array (
      'method' => 'GET',
      'path' => '/about/team',
      'original_path' => '/about/team',
      'handler' => 'public/About/About@team',
      'middleware' => 
      array (
      ),
      'name' => 'about.team',
      'params' => 
      array (
      ),
    ),
    'about.contact' => 
    array (
      'method' => 'GET',
      'path' => '/about/contact',
      'original_path' => '/about/contact',
      'handler' => 'public/About/About@contact',
      'middleware' => 
      array (
      ),
      'name' => 'about.contact',
      'params' => 
      array (
      ),
    ),
    'about.contact.post' => 
    array (
      'method' => 'POST',
      'path' => '/about/contact',
      'original_path' => '/about/contact',
      'handler' => 'public/About/About@contact',
      'middleware' => 
      array (
      ),
      'name' => 'about.contact.post',
      'params' => 
      array (
      ),
    ),
    'auth.login' => 
    array (
      'method' => 'GET',
      'path' => '/login',
      'original_path' => '/login',
      'handler' => 'public/Auth/AuthController@login',
      'middleware' => 
      array (
      ),
      'name' => 'auth.login',
      'params' => 
      array (
      ),
    ),
    'auth.login.post' => 
    array (
      'method' => 'POST',
      'path' => '/login',
      'original_path' => '/login',
      'handler' => 'public/Auth/AuthController@login',
      'middleware' => 
      array (
      ),
      'name' => 'auth.login.post',
      'params' => 
      array (
      ),
    ),
    'auth.register' => 
    array (
      'method' => 'GET',
      'path' => '/register',
      'original_path' => '/register',
      'handler' => 'public/Auth/AuthController@register',
      'middleware' => 
      array (
      ),
      'name' => 'auth.register',
      'params' => 
      array (
      ),
    ),
    'auth.register.post' => 
    array (
      'method' => 'POST',
      'path' => '/register',
      'original_path' => '/register',
      'handler' => 'public/Auth/AuthController@register',
      'middleware' => 
      array (
      ),
      'name' => 'auth.register.post',
      'params' => 
      array (
      ),
    ),
    'auth.logout' => 
    array (
      'method' => 'GET',
      'path' => '/logout',
      'original_path' => '/logout',
      'handler' => 'public/Auth/AuthController@logout',
      'middleware' => 
      array (
      ),
      'name' => 'auth.logout',
      'params' => 
      array (
      ),
    ),
    'auth.forgot' => 
    array (
      'method' => 'GET',
      'path' => '/forgot-password',
      'original_path' => '/forgot-password',
      'handler' => 'public/Auth/AuthController@forgotPassword',
      'middleware' => 
      array (
      ),
      'name' => 'auth.forgot',
      'params' => 
      array (
      ),
    ),
    'auth.forgot.post' => 
    array (
      'method' => 'POST',
      'path' => '/forgot-password',
      'original_path' => '/forgot-password',
      'handler' => 'public/Auth/AuthController@forgotPassword',
      'middleware' => 
      array (
      ),
      'name' => 'auth.forgot.post',
      'params' => 
      array (
      ),
    ),
    'blog.index' => 
    array (
      'method' => 'GET',
      'path' => '/blog',
      'original_path' => '/blog',
      'handler' => 'public/Blog/Blog@index',
      'middleware' => 
      array (
      ),
      'name' => 'blog.index',
      'params' => 
      array (
      ),
    ),
    'blog.show' => 
    array (
      'method' => 'GET',
      'path' => '/blog/{id:\\d+}',
      'original_path' => '/blog/{id:\\d+}',
      'handler' => 'public/Blog/Blog@show',
      'middleware' => 
      array (
      ),
      'name' => 'blog.show',
      'params' => 
      array (
      ),
    ),
    'blog.category' => 
    array (
      'method' => 'GET',
      'path' => '/blog/category/{category}',
      'original_path' => '/blog/category/{category}',
      'handler' => 'public/Blog/Blog@category',
      'middleware' => 
      array (
      ),
      'name' => 'blog.category',
      'params' => 
      array (
      ),
    ),
    'header' => 
    array (
      'method' => 'GET',
      'path' => '/header',
      'original_path' => '/header',
      'handler' => 'public/Common/Header@index',
      'middleware' => 
      array (
      ),
      'name' => 'header',
      'params' => 
      array (
      ),
    ),
    'footer' => 
    array (
      'method' => 'GET',
      'path' => '/footer',
      'original_path' => '/footer',
      'handler' => 'public/Common/Footer@index',
      'middleware' => 
      array (
      ),
      'name' => 'footer',
      'params' => 
      array (
      ),
    ),
    'home' => 
    array (
      'method' => 'GET',
      'path' => '/',
      'original_path' => '/',
      'handler' => 'public/Home/Home@index',
      'middleware' => 
      array (
      ),
      'name' => 'home',
      'params' => 
      array (
      ),
    ),
    'home.get-started' => 
    array (
      'method' => 'GET',
      'path' => '/get-started',
      'original_path' => '/get-started',
      'handler' => 'public/Home/Home@getStarted',
      'middleware' => 
      array (
      ),
      'name' => 'home.get-started',
      'params' => 
      array (
      ),
    ),
    'automate.index' => 
    array (
      'method' => 'GET',
      'path' => '/automate',
      'original_path' => '/automate',
      'handler' => 'automate/Generator/Generator@index',
      'middleware' => 
      array (
      ),
      'name' => 'automate.index',
      'params' => 
      array (
      ),
    ),
    'automate.generate' => 
    array (
      'method' => 'POST',
      'path' => '/automate/generate',
      'original_path' => '/automate/generate',
      'handler' => 'automate/Generator/Generator@generate',
      'middleware' => 
      array (
      ),
      'name' => 'automate.generate',
      'params' => 
      array (
      ),
    ),
    'automate.list' => 
    array (
      'method' => 'GET',
      'path' => '/automate/list',
      'original_path' => '/automate/list',
      'handler' => 'automate/Generator/Generator@list',
      'middleware' => 
      array (
      ),
      'name' => 'automate.list',
      'params' => 
      array (
      ),
    ),
  ),
  'generated_at' => 1760539921,
);

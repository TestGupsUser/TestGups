<?php






return [
    'activate/<token:[\w\-]+>'                                => 'account/activate',
    'admin/ban/<id:\d+>'                                      => 'admin/ban',
    'admin/contents/<name:[\w\-]+>'                           => 'admin/contents',
    'admin/delete/<id:\d+>'                                   => 'admin/delete',
    'admin/delete-category/<id:\d+>'                          => 'admin/delete-category',
    'admin/delete-forum/<cid:\d+>/<id:\d+>'                   => 'admin/delete-forum',
    'admin/edit-category/<id:\d+>'                            => 'admin/edit-category',
    'admin/edit-forum/<cid:\d+>/<id:\d+>'                     => 'admin/edit-forum',
    'admin/forums/<cid:\d+>'                                  => 'admin/forums',
    'admin/mod/<uid:\d+>/<fid:\d+>'                           => 'admin/mod',
    'admin/mods/<id:\d+>'                                     => 'admin/mods',
    'admin/new-forum/<cid:\d+>'                               => 'admin/new-forum',
    'admin/pm/<id:\d+>'                                       => 'admin/pm',
    'admin/update/<id:\d+>'                                   => 'admin/update',
    'admin/view/<id:\d+>'                                     => 'admin/view',
    'admin'                                                   => 'admin/index',
    'category/<id:\d+>/<slug:[\w\-]+>'                        => 'forum/category',
    'delete/<cid:\d+>/<fid:\d+>/<id:\d+>/<slug:[\w\-]+>'      => 'forum/delete',
    'deletepoll/<cid:\d+>/<fid:\d+>/<tid:\d+>/<pid:\d+>'      => 'forum/deletepoll',
    'deletepost/<cid:\d+>/<fid:\d+>/<tid:\d+>/<pid:\d+>'      => 'forum/deletepost',
    'deleteposts/<cid:\d+>/<fid:\d+>/<id:\d+>/<slug:[\w\-]+>' => 'forum/deleteposts',
    'demote/<id:\d+>'                                         => 'admin/demote',
    'edit/<cid:\d+>/<fid:\d+>/<tid:\d+>/<pid:\d+>'            => 'forum/edit',
    'editpoll/<cid:\d+>/<fid:\d+>/<tid:\d+>/<pid:\d+>'        => 'forum/editpoll',
    'forum/<cid:\d+>/<id:\d+>/<slug:[\w\-]+>/<toggle:\w+>'    => 'forum/forum',
    'forum/<cid:\d+>/<id:\d+>/<slug:[\w\-]+>'                 => 'forum/forum',
    'home'                                                    => 'forum/index',
    'install'                                                 => 'install/run',
    'last/<id:\d+>'                                           => 'forum/last',
    'level-up'                                                => 'install/level-up',
    'lock/<cid:\d+>/<fid:\d+>/<id:\d+>/<slug:[\w\-]+>'        => 'forum/lock',
    'login'                                                   => 'account/login',
    'logout'                                                  => 'profile/logout',
    'maintenance'                                             => 'forum/maintenance',
    'mark-seen'                                               => 'forum/mark-seen',
    'members/friend/<id:\d+>'                                 => 'members/friend',
    'members/posts/<id:\d+>/<slug:[\w\-]+>'                   => 'members/posts',
    'members/threads/<id:\d+>/<slug:[\w\-]+>'                 => 'members/threads',
    'members/view/<id:\d+>/<slug:[\w\-]+>'                    => 'members/view',
    'members'                                                 => 'members/index',
    'members/ignore/<id:\d+>'                                 => 'members/ignore',
    'messages/delete-received/<id:\d+>'                       => 'messages/delete-received',
    'messages/delete-sent/<id:\d+>'                           => 'messages/delete-sent',
    'messages/new/<user:\d+>'                                 => 'messages/new',
    'messages/reply/<id:\d+>'                                 => 'messages/reply',
    'messages/view-received/<id:\d+>'                         => 'messages/view-received',
    'messages/view-sent/<id:\d+>'                             => 'messages/view-sent',
    'move/<cid:\d+>/<fid:\d+>/<id:\d+>/<slug:[\w\-]+>'        => 'forum/move',
    'moveposts/<cid:\d+>/<fid:\d+>/<id:\d+>/<slug:[\w\-]+>'   => 'forum/moveposts',
    'new-email/<token:[\w\-]+>'                               => 'account/new-email',
    'new-thread/<cid:\d+>/<fid:\d+>'                          => 'forum/new-thread',
    'pin/<cid:\d+>/<fid:\d+>/<id:\d+>/<slug:[\w\-]+>'         => 'forum/pin',
    'post/<cid:\d+>/<fid:\d+>/<tid:\d+>/<pid:\d+>'            => 'forum/post',
    'post/<cid:\d+>/<fid:\d+>/<tid:\d+>'                      => 'forum/post',
    'profile'                                                 => 'profile/index',
    'profile/add/<id:\d+>'                                    => 'profile/add',
    'profile/delete/<id:\d+>'                                 => 'profile/delete',
    'profile/mark/<id:\d+>'                                   => 'profile/mark',
    'promote/<id:\d+>'                                        => 'admin/promote',
    'reactivate'                                              => 'account/reactivate',
    'register'                                                => 'account/register',
    'report/<cid:\d+>/<fid:\d+>/<tid:\d+>/<pid:\d+>'          => 'forum/report',
    'reset'                                                   => 'account/reset',
    'search'                                                  => 'forum/search',
    'show/<id:\d+>'                                           => 'forum/show',
    'thread/<cid:\d+>/<fid:\d+>/<id:\d+>/<slug:[\w\-]+>'      => 'forum/thread',
    'unread-posts'                                            => 'forum/unread-posts',
];

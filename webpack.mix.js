/* webpack.mix.js ----------------------------------------------------- */
// const mix  = require('laravel-mix');
// const path = require('path');              // ← add this

// mix.js('resources/js/app.js', 'public/js')
//    .vue()
//    .sass('resources/sass/app.scss', 'public/css')
//    .version()
//    .setPublicPath('public')

//    /* ---------- alias so "@/…" points to resources/js --------------- */
//    .alias({
//         '@': path.join(__dirname, 'resources/js'),   // now @ works everywhere
//    });
/* webpack.mix.js ----------------------------------------------------- */
const mix  = require('laravel-mix')
const path = require('path')

mix.js('resources/js/app.js', 'public/js')
   .vue()

   /*  quietDeps ➜ подавляем предупреждения “green() is deprecated”…   */
   .sass(
       'resources/sass/app.scss',
       'public/css',
       { sassOptions: { quietDeps: true } }   // ← добавили эту строку
   )

   .version()
   .setPublicPath('public')

   /* алиас, чтобы "@/…" вело в resources/js */
   .alias({
      '@': path.join(__dirname, 'resources/js')
   })

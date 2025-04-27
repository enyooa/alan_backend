/* webpack.mix.js ----------------------------------------------------- */
const mix  = require('laravel-mix');
const path = require('path');              // ← add this

mix.js('resources/js/app.js', 'public/js')
   .vue()
   .sass('resources/sass/app.scss', 'public/css')
   .version()
   .setPublicPath('public')

   /* ---------- alias so "@/…" points to resources/js --------------- */
   .alias({
        '@': path.join(__dirname, 'resources/js'),   // now @ works everywhere
   });

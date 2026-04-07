# BASE-API-FIREBASE
Base Api Firebase By Putra, use nwidart
Setup base api firebase
php artisan migrate
php artisan db:seed --class=RoleSeeder
php artisan key:generate

**Pastikan Env Sudah Benar dan firebasejson nya sudah di isi** !!

1. bikin akun firebase
2. copy private key |  setting → firebase admin sdk → generate new private key
3. autentication → sign method → add new provider, email, google

# Google Drive Clone

Build and deploy a fully functional file manager application with Laravel, PHP, Inertial, and Vue.js. The application is similar to Google Drive.


## Expressing thanks

Thanks for Zura for his amazing video about creating file manager using laravel and vue.js 


## What I added

In my version of this project I added some modification to the original code 

- previously the downloaded files which generated when user click on download button are saved in `storage/app/public` and then symbolic link was created from `public/storage` to `storage/app/public`. But then i realized that any one has the link can download the file from `storage/app/public` even if he was not the creator of that file so i decided to save the files which user created by clicking on download button in `storage/app/download` and save the information of that file in database (so i create new `download` table ) this information include the generated zip file name and the creator id ...etc , then created new route to handel the download proccess and this is the main idea so in the controller i checked if the file is created by the logged authentication user if it was the download starts and if not the user is redirected to 403 page.
- When the download file is created and stored in storage/app/download it added to job `DeleteDownloadedFile` which delete the file form storage and his information from download table after six hours.
- I created unshare button to enable user to unshare his file.
- I added multiple file selection using shift key and mouse click.
- Some other changes in code like defining more relation in models and use them in controller


## Course Link

In the end i recommend this course to everyone, this is link to course video in [youtube](https://www.youtube.com/watch?v=Wn3IPX_ax-0&list=LL&index=10)


## 🔗 Links
[![linkedin](https://img.shields.io/badge/linkedin-0A66C2?style=for-the-badge&logo=linkedin&logoColor=white)](https://www.linkedin.com/in/gibran-kashour-a073471b2/)

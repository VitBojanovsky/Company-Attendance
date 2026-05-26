# Documentation for the best project ever
## Intro
This project was supposed to log attendance of employees or students. Now this a bit of an open assignment for me so I was kind of lost in it. Not for the technical complexity, but just because I did not know what to do, like what is this project supposed to do? There are no requirements, no grading criteria, no nothing.
So what I decided to do was just a website that has a form in which an employee enters their id, time in, and time out. 
## Security
The security relies on the fact that other people will not know other peoples id, this is not ideal, however INT has a lot of numbers, so maybe with some rate limiting this could work. 
There is no signup, the employees are managed by the admin. The admins login is hard coded (added during db initialization), which is in the webroot inside /scripts/, which is a big security risk, because anyone who knows the path can access it and see the credentials. Even if you rewrite admin password, the DB inicialiyation script will overwrite it, so maybe a part of the deployment should be to, after suecesfull migration, to delete /scripts/migrate_db.php. 
When employee enters time out < time in, the system will calculate the time correctly.
Overall the security of this project was not a priority at all, I did the bare minimum to get it working. Not that I do not care, but this just an assignment, not a real system, that real people will rely on. You could argue, that this is a showcase of my skills and that it should be made to my highest abilities, however, I have other projects that I will use as a reference for my future work, not this one, I will never work on websites in the future (hopefully).
Maybe I will implement some security measures in the future, but for now this is the bare minimum.
##

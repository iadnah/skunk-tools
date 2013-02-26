/*
  passgen

  generates all combinations of the given keyspace (Keyspace) between the min and max
  lengths and spits to stdout. The idea is to pipe the output into another program

*/
#include <stdlib.h>
const char Keyspace[] ="abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890"; 
int main(int argc, char *argv[])
{
  if (argc < 3) {
  	printf("Usage: passgen <minimum length> <maxlength>\n Keyspace: %s\n", Keyspace);
  	return 1;
  }
  
  char Password[64];
  int MaxPasswordLength = atoi(argv[2]);
  int a, b;
  a = atoi(argv[1]);
  int i;
  a = a - 1;
 
  for (a; a < MaxPasswordLength; a++) {
   for(b=0; b <= a; b++) { 
   	Password[b]=Keyspace[0]; 
   }
   
   Password[b]='\0';
   b=0;
   while(b <= a) {
    if(!b) { 
    	printf("%s\n", Password); 
    }

    i = 0;

    while(Keyspace[i]) { 
    	if(Password[b] == Keyspace[i]) { 
    		break; 
    	} 
    	i++; 
    } 
    i++;

    if(i >= strlen(Keyspace)) { 
    	Password[b] = Keyspace[0]; b++; continue; 
    }

    Password[b] = Keyspace[i];
    b = 0;
   } 
  } 

 return 0;
}

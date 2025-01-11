# Civil-Records Setup

## Clean or New Install

- Donwload the package
- Unzip the files into an empty folder on your server.
- Create a domain or subdomain pointing to the Civil-Records public folder.
- Start Civil-records at http://your-subdomain.
- The setup installer will start automatically.
- You just have to follow the instructions.

## Update

- create an empty folder on your server.
- Donwload the package
- Unzip the files into the folder you created.
- Move or copy your last _storage directiory inside the new folder.
- Move or copy your last .env file inside the new folder.
- Rename last Civil-Record directory to latest.
- Rename the new folder like your latest name.
- Start Civil-records at http://your-subdomain.
- The setup updater will start automatically.
- You just have to follow the instructions.

## Update from Expoactes 3.2.6

- Coming soon

## Server Specifics

Civil-Record is developed to be an application and not a module.
Civil-record only manages (for the moment) one database.

If you have an Apache server, you will need an htaccess file in the public folder.
An example is provided with the package.

If you are under Nginx, we do not test Civil-Record on this server.
Please adapt the htaccess file in config for Nginx.

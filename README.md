# Stream To File
## Description
Simple Extension for the [Stream Plugin] (https://en-gb.wordpress.org/plugins/stream/) that generates a log file, with individual Stream records.

### Why?
writing to file, means you can use standard tools to look at the data, it also means you don't need to interact with the site to look at user activity.

## Installation 
As a normal WordPress Plugin, note Stream must be installed.
- Once Activate go to Stream Settings -> Log File
- Set a path and if you wish to change the format modify

Format options:
- {user_id}
- {user_role}
- {created}
- {summary}
- {connector}
- {context}
- {action}
- {ip}
- {display_name}
- {user_email}
- {user_login}

### IMPORTANT
This plugin does not do any log rotation, if you clear Stream, or have stream rotating logs every x days these will not affect the text log. This is intentional, if you want to rotate logs I suggest using something like logrotate:  
```
/var/www/vhosts/example.com/logs/wordpress-activity.log {
    weekly
    maxsize         20M
    maxage          28
    rotate          4
    copytruncate
    nomail
    compress
    missingok
}
```
Will set it to weekly or if reaches 20M rotate put the archives in gzip files and delete after 28 days.
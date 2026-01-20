# Environment Config Matrix

ตารางสรุปค่าที่ควรใช้ตามสภาพแวดล้อม

| Key | Development | Staging | Production |
|---|---|---|---|
| APP_ENV | development | staging | production |
| APP_DEBUG | true | false | false |
| APP_URL | http://localhost | https://staging.example.com | https://example.com |
| DB_* | dev db | staging db | production db |
| MAIL_* | dev smtp | staging smtp | production smtp |
| API_KEY | dev key | staging key | production key |

หมายเหตุ: อย่า commit ค่า credentials จริงขึ้น repo

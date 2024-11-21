
http://54.185.122.131/api/create-first-admin
Content-Type: application/json
{
    "name": "Admin Macrobillingec",
    "email": "admin@macrobillingec.com",
    "password": "macrobilling*123",
    "password_confirmation": "macrobilling*123"
}


http://54.185.122.131/oauth/token
Content-Type: application/json
{
    "grant_type": "password",
    "client_id": "3",
    "client_secret": "vESMJmm8RqxC7Mm16YGk3WyIx455qR7GkmFzocD0",
    "username": "admin@macrobillingec.com",
    "password": "macrobilling*123",
    "scope": "admin user"
}


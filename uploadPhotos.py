import gdata
import gdata.photos.service
import os
import argparse
import webbrowser
import httplib2

from datetime import datetime, timedelta
from oauth2client.client import flow_from_clientsecrets
from oauth2client.file import Storage

# Make sure to make the folder here and drop your client secrets or change the path
configdir = os.path.expanduser('~/.config/GooglePhotosConfig')
client_secrets = os.path.join(configdir, 'client_secrets.json')
credential_store = os.path.join(configdir, 'credentials.dat')


def OAuth2Login(client_secrets, credential_store, email):
    scope='https://picasaweb.google.com/data/'
    user_agent='GooglePhotoUploader'

    storage = Storage(credential_store)
    credentials = storage.get()
    if credentials is None or credentials.invalid:
        flow = flow_from_clientsecrets(client_secrets, scope=scope, redirect_uri='urn:ietf:wg:oauth:2.0:oob')
        uri = flow.step1_get_authorize_url()
        webbrowser.open(uri)
        code = raw_input('Enter the authentication code: ').strip()
        credentials = flow.step2_exchange(code)

    if (credentials.token_expiry - datetime.utcnow()) < timedelta(minutes=5):
        http = httplib2.Http()
        http = credentials.authorize(http)
        credentials.refresh(http)

    storage.put(credentials)

    gd_client = gdata.photos.service.PhotosService(source=user_agent,
                                                   email=email,
                                                   additional_headers={'Authorization' : 'Bearer %s' % credentials.access_token})

    return gd_client

parser = argparse.ArgumentParser(description='Uploads photos to Google Photos')
parser.add_argument('--path', help='Path to the specified file', required=True)
parser.add_argument('--username', help='Google username no @gmail.com', required=True)
parser.add_argument('--albumID', help='Google Photo Album ID', required=True)
parser.add_argument('--email', help='Google Email', required=True)
args = parser.parse_args()
filePath = args.path
fileName = os.path.basename(filePath)
usrName = args.username
album = args.albumID
email = args.email

#OAuth2 for Google
gd_client = OAuth2Login(client_secrets, credential_store, email)

#Add the image to the specified folder
album_url = '/data/feed/api/user/%s/albumid/%s' % (usrName,album)
photo = gd_client.InsertPhotoSimple(album_url, fileName, 
    'Uploaded from Photobooth', filePath, content_type='image/jpeg')
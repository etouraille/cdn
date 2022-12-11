const express = require('express');
const fileUpload = require('express-fileupload');
const cors = require('cors');
const bodyParser = require('body-parser');
const morgan = require('morgan');
const _ = require('lodash');
const sharp = require('sharp');
const app = express();
const { v4: uuidV4 } = require('uuid');
const fs = require('fs');
const path = require('path');
const jwt = require('jsonwebtoken')


const SECRET = fs.readFileSync( './jwt/public.pem');
/* Récupération du header bearer */
const extractBearerToken = headerValue => {
    if (typeof headerValue !== 'string') {
        return false
    }

    const matches = headerValue.match(/(bearer)\s+(\S+)/i)
    return matches && matches[2]
}

/* Vérification du token */
const checkTokenMiddleware = (req, res, next) => {
    // Récupération du token
    const token = req.headers.authorization && extractBearerToken(req.headers.authorization)

    // Présence d'un token
    if (!token) {
        return res.status(401).json({ message: 'Error. Need a token' })
    }

    // Véracité du token
    jwt.verify(token, SECRET, (err, decodedToken) => {
        if (err) {
            res.status(401).json({ message: 'Error. Bad token' })
        } else {
            return next()
        }
    })
}
// enable files upload

app.use(fileUpload({
    createParentPath: true
}));

//add other middleware
app.use(express.static('public'));
app.use(cors());
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({extended: true}));
app.use(morgan('dev'));

app.delete('/delete-picture/:file', checkTokenMiddleware, async( req, res ) => {
    let file = req.params.file;
    try {
        fs.unlinkSync('./public/' + file);
        return res.json({ success: true});
    } catch (error) {
        return res.json({ success: true, err: error});
    }
});

app.post('/upload', checkTokenMiddleware,async (req, res) => {
    try {
        if(!req.files) {
            res.send({
                status: false,
                message: 'No file uploaded'
            });
        } else {
            //Use the name of the input field (i.e. "avatar") to retrieve the uploaded file
            let file = req.files.file;

            //Use the mv() method to place the file in the upload directory (i.e. "uploads")
            file.mv('./public/' + file.name).finally(() => {

                var fileExt = file.name.split('.').pop().toLowerCase();

                var uuid = uuidV4();
                var filename = null;

                if(['png', 'jpeg', 'jpg', 'bmp', 'gif'].includes(fileExt)) {
                    sharp('./public/' + file.name)
                        .flatten({ background: { r: 255, g: 255, b: 255, alpha: 255 } })
                        .jpeg({quality: 100})
                        .toFile('./public/' + uuid + '.jpeg')
                        .then((data) => {
                            console.log(data);
                            fs.unlinkSync('./public/' + file.name);
                        }, err => console.log(err))
                    ;
                    filename = uuid + '.jpeg'

                } else {
                    fs.renameSync('./public/' + file.name, './public/' + uuid + '.' + fileExt);
                    filename = uuid + '.' + fileExt;
                }

                //send response
                res.send({
                    status: true,
                    message: 'File is uploaded',
                    data: {
                        name: filename,
                        mimetype: file.mimetype,
                        size: file.size
                    }
                });
            })


        }
    } catch (err) {
        res.status(500).send(err);
    }
});


//start app
const port = process.env.PORT || 3000;

app.listen(port, () =>
    console.log(`App is listening on port ${port}.`)
);
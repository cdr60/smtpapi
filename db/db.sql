--
-- Fichier généré par SQLiteStudio v3.2.1 sur mar. oct. 25 21:28:28 2022
--
-- Encodage texte utilisé : ISO-8859-1
--
PRAGMA foreign_keys = off;
BEGIN TRANSACTION;

-- Table : TBMAIL
DROP TABLE IF EXISTS TBMAIL;

CREATE TABLE TBMAIL (
    IDMAIL     INTEGER       PRIMARY KEY AUTOINCREMENT,
    SMTPSERVER VARCHAR (255) NOT NULL,
    SMTPPORT   INTEGER       NOT NULL,
    SMTPLOGIN  VARCHAR (255) NOT NULL,
    SMTPPASS   VARCHAR (255) NOT NULL,
    SMTPAUTH   BOOLEAN       NOT NULL
                             DEFAULT (FALSE),
    SMTPSECURE VARCHAR (8),
    REPLYTO    VARCHAR (255),
    SENDER     VARCHAR (255) NOT NULL,
    SUBJECT    VARCHAR (255),
    BODY       TEXT,
    SEND       BOOLEAN       NOT NULL
                             DEFAULT (FALSE),
    SENDTS     DATETIME,
    CREATETS   DATETIME      DEFAULT (CURRENT_TIMESTAMP),
    WITHAR     BOOLEAN       NOT NULL
                             DEFAULT (FALSE) 
);



-- Table : TBMAILATTACH
DROP TABLE IF EXISTS TBMAILATTACH;
CREATE TABLE TBMAILATTACH (
    IDMAIL                 CONSTRAINT FK_ATTACH_MAIL REFERENCES TBMAIL (IDMAIL) ON DELETE CASCADE
                                                                                ON UPDATE CASCADE,
    IDATTACH INTEGER       PRIMARY KEY AUTOINCREMENT
                           NOT NULL,
    FILENAME VARCHAR (255) NOT NULL,
    FILEDATA BLOB          NOT NULL
);


-- Table : TBMAILDEST
DROP TABLE IF EXISTS TBMAILDEST;
CREATE TABLE TBMAILDEST (
    IDMAIL   INTEGER       CONSTRAINT FK_RCPT_MAIL REFERENCES TBMAIL (IDMAIL) ON DELETE CASCADE
                                                                              ON UPDATE CASCADE
                           NOT NULL,
    IDDEST   INTEGER       PRIMARY KEY AUTOINCREMENT
                           NOT NULL,
    DEST     VARCHAR (255) NOT NULL,
    TYPEDEST VARCHAR (3)   NOT NULL
                           DEFAULT CC
);


-- Table : TBPARAM
DROP TABLE IF EXISTS TBPARAM;
CREATE TABLE TBPARAM (
    CPARAM VARCHAR (16)  PRIMARY KEY
                         NOT NULL,
    VPARAM VARCHAR (255) 
)
WITHOUT ROWID;

INSERT INTO TBPARAM (
                        CPARAM,
                        VPARAM
                    )
                    VALUES (
                        'PASSWORD',
                        'password'
                    );

INSERT INTO TBPARAM (
                        CPARAM,
                        VPARAM
                    )
                    VALUES (
                        'USERNAME',
                        'admin'
                    );


COMMIT TRANSACTION;
PRAGMA foreign_keys = on;

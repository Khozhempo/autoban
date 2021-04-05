// autoban
package main

import (
	"bytes"
	"database/sql"

	// "fmt"
	"io/ioutil"
	"math/big"
	"net"
	"net/http"
	"regexp"
	"strconv"
	"strings"
	"time"

	"github.com/hjson/hjson-go"

	"github.com/gin-gonic/gin"
	_ "github.com/go-sql-driver/mysql"
)

var (
	siteKeys     map[string]int
	searchRobots map[string]int
	validUID     = regexp.MustCompile(`^([A-Fa-f0-9]{36})$`)
	rootKey      = "rootkey" // key of service to reload configuration, use in test_autoban_reload.php and web-admin
)

type receiveIpStruct struct {
	siteid     int
	ip         int64
	date       int
	url        string
	ownervisit int
	agent      string
	refer      string
	method     string
}

func main() {
	mainConfig := readCfgFile("config.json") // чтение конфигурации
	var dbConnect = mainConfig["dbUser"].(string) + ":" + mainConfig["dbPsw"].(string) + "@tcp(" + mainConfig["dbAddress"].(string) + ":" + FloatToString(mainConfig["dbPort"].(float64)) + ")/" + mainConfig["dbBase"].(string)

	//	gin.SetMode(gin.ReleaseMode)	// можно включить для скрытия debug информации

	// Initializing DB connection
	db, err := sql.Open("mysql", dbConnect)
	checkErr(err)
	defer db.Close()
	db.SetConnMaxLifetime(time.Second * 100)

	// загрузка базы ключ=сайт
	siteKeys := loadSiteKeys(db)
	searchRobots := loadRobotsKeys(db)
	banAgents := loadBanAgentsKeys(db)

	router := gin.Default()
	router.POST(mainConfig["httpAddFolder"].(string)+"/receive", func(c *gin.Context) {
		var receiveIP receiveIpStruct
		uid := c.Query("uid")
		if validUID.MatchString(uid) { // проверка на корректный uid
			siteid := siteKeys[uid]
			if siteid > 0 { // проверка на наличие uid в базе
				receiveIP = fillReceiveIP(siteid, c)

				// check searchrobots in UserAgent and mark it
				if checkSearchRobot(c.PostForm("agent"), searchRobots) {
					receiveIP.ownervisit = 2
				}

				dbSaveIp(db, receiveIP)
				c.String(http.StatusOK, "statusOK")
				if checkSuspicious(receiveIP.url, receiveIP.method) == 1 && receiveIP.ownervisit == 0 { // если подозрительное поведение и это не собственник, то в бан
					updateBanIPInBase(db, receiveIP.ip, 2, siteid)
				}
			} else {
				//				c.String(http.StatusForbidden, "invalid uid")
				c.String(http.StatusOK, "invalid uid")
			}
		} else {
			//			c.String(http.StatusForbidden, "invalid uid")
			c.String(http.StatusOK, "invalid uid")
		}
	})

	router.POST(mainConfig["httpAddFolder"].(string)+"/check", func(c *gin.Context) {
		var receiveIP receiveIpStruct
		uid := c.Query("uid")
		if validUID.MatchString(uid) { // проверка на корректный uid
			siteid := siteKeys[uid]
			if siteid > 0 { // проверка на наличие uid в базе
				receiveIP = fillReceiveIP(siteid, c)

				// check searchrobots in UserAgent and mark it
				if checkSearchRobot(c.PostForm("agent"), searchRobots) {
					receiveIP.ownervisit = 2
				}
				//				c.String(http.StatusOK, "statusOK")
				// проверка на присутствие в банлисте
				checkIP := dbCheckIP(db, receiveIP, siteid)
				if checkBanAgent(c.PostForm("agent"), banAgents) { // если токсичный userAgent
					c.String(http.StatusOK, "IP banned") // отвечаем отказом
					// updateBanIPInBase(db, receiveIP.ip, 0, siteid)
					// dbSaveIp(db, receiveIP)
				} else {
					if checkIP == "IP permited" { // если нет в бан листе, то проверяем на подозрительное поведение
						checkSusp := checkSuspicious(receiveIP.url, receiveIP.method)
						if checkSusp == 0 || receiveIP.ownervisit != 0 { // проверка на подозрительное поведение
							c.String(http.StatusOK, checkIP)
							dbSaveIp(db, receiveIP)
						} else if checkSusp == 1 && receiveIP.ownervisit == 0 {
							c.String(http.StatusOK, "IP banned")
							updateBanIPInBase(db, receiveIP.ip, 2, siteid)
							dbSaveIp(db, receiveIP)
						} else {
							dbSaveIp(db, receiveIP)
						}
					} else {
						c.String(http.StatusOK, checkIP) // если в банлисте, то только отвечаем отказом
					}
				}

			} else {
				//				c.String(http.StatusForbidden, "invalid uid")
				c.String(http.StatusOK, "invalid uid")
			}
		} else {
			//			c.String(http.StatusForbidden, "invalid uid")
			c.String(http.StatusOK, "invalid uid")
		}
	})

	router.GET(mainConfig["httpAddFolder"].(string)+"/reload", func(c *gin.Context) {
		if c.Query("rootKey") == rootKey {
			siteKeys = loadSiteKeys(db)
			searchRobots = loadRobotsKeys(db)
			banAgents = loadBanAgentsKeys(db)
			c.String(http.StatusOK, "SiteKeys reloaded successfully")
		}
	})

	router.Run(":8080")
}

// ----- FUNCTIONS -----
// чтение конфиг файла
func readCfgFile(filename string) map[string]interface{} {
	configFileText, err := ioutil.ReadFile(filename)
	if err != nil {
		panic(err)
	}

	var dat map[string]interface{}
	// Decode and a check for errors.
	if err := hjson.Unmarshal(configFileText, &dat); err != nil {
		panic(err)
	}

	return dat
}

// запись в таблицу визитов
func dbSaveIp(db *sql.DB, receiveIP receiveIpStruct) {
	stmt, err := db.Prepare("INSERT allvisits SET ip=?, date=?, url=?, siteid=?, ownervisit=?, agent=?, refer=?, method=?")
	var method uint
	//	method = 0
	checkErr(err)

	if receiveIP.method == "GET" {
		method = 0
	} else {
		method = 1
	}
	res, err := stmt.Exec(receiveIP.ip, receiveIP.date, receiveIP.url, receiveIP.siteid, receiveIP.ownervisit, receiveIP.agent, receiveIP.refer, method)
	checkErr(err)
	_ = res

}

// проверка ip на наличие ban листе
func dbCheckIP(db *sql.DB, receiveIP receiveIpStruct, siteid int) string {
	var ip int64 //uint
	query := "SELECT ip FROM blacklistip WHERE siteid = ?"
	mainQuery, err := db.Query(query, siteid)
	checkErr(err)
	defer mainQuery.Close()

	for mainQuery.Next() {
		err := mainQuery.Scan(&ip)
		checkErr(err)
		if compareIP(InttoIP4(receiveIP.ip), InttoIP4(ip)) == 1 {
			return "IP banned"
		}
	}

	return "IP permited"
}

// проверка соответствия двух ip адресов
func compareIP(receivedIP_in string, bannedIP_in string) int {
	var (
		bannedIP_start net.IP
		bannedIP_end   net.IP
	)

	bannedIP := net.ParseIP(bannedIP_in)
	receivedIP := net.ParseIP(receivedIP_in)

	IPv4Int := big.NewInt(0)
	IPv4Int.SetBytes(bannedIP.To4())

	ipInt := IPv4Int.Int64()
	b0 := strconv.FormatInt((ipInt>>24)&0xff, 10)
	b0i := (ipInt >> 24) & 0xff
	b1 := strconv.FormatInt((ipInt>>16)&0xff, 10)
	b1i := (ipInt >> 16) & 0xff
	b2 := strconv.FormatInt((ipInt>>8)&0xff, 10)
	b2i := (ipInt >> 8) & 0xff
	b3 := strconv.FormatInt((ipInt & 0xff), 10)
	b3i := ipInt & 0xff
	if b0i == 255 && b1i == 255 && b2i == 255 && b3i == 255 {
		bannedIP_start = net.ParseIP("0.0.0.0")
		bannedIP_end = net.ParseIP("255.255.255.255")
	} else if b1i == 255 && b2i == 255 && b3i == 255 {
		bannedIP_start = net.ParseIP(b0 + ".0.0.0")
		bannedIP_end = net.ParseIP(b0 + ".255.255.255")
	} else if b2i == 255 && b3i == 255 {
		bannedIP_start = net.ParseIP(b0 + "." + b1 + ".0.0")
		bannedIP_end = net.ParseIP(b0 + "." + b1 + ".255.255")
	} else if b3i == 255 {
		bannedIP_start = net.ParseIP(b0 + "." + b1 + "." + b2 + ".0")
		bannedIP_end = net.ParseIP(b0 + "." + b1 + "." + b2 + ".255")
	} else {
		bannedIP_start = net.ParseIP(b0 + "." + b1 + "." + b2 + "." + b3)
		bannedIP_end = bannedIP_start
	}

	if bytes.Compare(receivedIP, bannedIP_start) >= 0 && bytes.Compare(receivedIP, bannedIP_end) <= 0 {
		return 1
	} else {
		return 0
	}
}

// загрузка карты key и siteid
func loadSiteKeys(db *sql.DB) map[string]int {
	var (
		uid    string
		siteid int
	)
	siteKeys := make(map[string]int)

	siteKeysQuery, err := db.Query("SELECT uid, siteid FROM sites WHERE active = 1 AND disabled = 0")
	checkErr(err)
	defer siteKeysQuery.Close()
	for siteKeysQuery.Next() {
		err := siteKeysQuery.Scan(&uid, &siteid)
		checkErr(err)
		siteKeys[uid] = siteid
	}
	return siteKeys
}

// загрузка карты key и siteid
func loadRobotsKeys(db *sql.DB) map[string]int {
	var (
		robotName string
		uid       int
	)
	robotsKeys := make(map[string]int)

	robotsKeysQuery, err := db.Query("SELECT robotname FROM search_robots")
	checkErr(err)
	defer robotsKeysQuery.Close()
	for robotsKeysQuery.Next() {
		err := robotsKeysQuery.Scan(&robotName)
		checkErr(err)
		robotsKeys[robotName] = uid
		uid++
	}
	return robotsKeys
}

// загрузка карты key и siteid
func loadBanAgentsKeys(db *sql.DB) map[string]int {
	var (
		robotName string
		uid       int
	)
	robotsKeys := make(map[string]int)

	robotsKeysQuery, err := db.Query("SELECT robotname FROM ban_agents")
	checkErr(err)
	defer robotsKeysQuery.Close()
	for robotsKeysQuery.Next() {
		err := robotsKeysQuery.Scan(&robotName)
		checkErr(err)
		robotsKeys[robotName] = uid
		uid++
	}
	return robotsKeys
}

// проверка ошибок
func checkErr(err error) {
	if err != nil {
		panic(err)
	}
}

// ipv4 2 int64
func IP4toInt(ipv4 string) int64 {
	IPv4Address := net.ParseIP(ipv4)
	IPv4Int := big.NewInt(0)
	IPv4Int.SetBytes(IPv4Address.To4())
	return IPv4Int.Int64()
}

// int64 2 ipv4
func InttoIP4(ipInt int64) string {
	// need to do two bit shifting and “0xff” masking
	b0 := strconv.FormatInt((ipInt>>24)&0xff, 10)
	b1 := strconv.FormatInt((ipInt>>16)&0xff, 10)
	b2 := strconv.FormatInt((ipInt>>8)&0xff, 10)
	b3 := strconv.FormatInt((ipInt & 0xff), 10)
	return b0 + "." + b1 + "." + b2 + "." + b3
}

// преобразование строки в число
func str2int(val string) int {
	i, _ := strconv.Atoi(val)
	return i
}

// to convert a float number to a string
func FloatToString(input_num float64) string {
	return strconv.FormatFloat(input_num, 'f', 0, 64)
}

// to convert a float number to uint
func FloatToUint(input_num float64) uint {
	return uint(input_num)
}

// проверка наличия имени робота в UserAgent
func checkSearchRobot(userAgent string, searchRobots map[string]int) bool {
	for v, _ := range searchRobots {
		if strings.Contains(userAgent, v) {
			return true
		}
	}

	return false
}

// проверка наличия имени нежелательного Agent в UserAgent
func checkBanAgent(userAgent string, banAgent map[string]int) bool {
	for v, _ := range banAgent {
		if strings.Contains(userAgent, v) {
			return true
		}
	}

	return false
}

// проверка подозрительной активности
func checkSuspicious(urlQuery string, method string) uint {
	// ФОРУМ, проверка публикации с активацией
	ifForumPostActivation_i := strings.Index(urlQuery, "mode=post")
	ifForumPostActivation_ii := strings.Index(urlQuery, "[+Activation+]")
	//	if (ifForumPostActivation_i + ifForumPostActivation_ii) > -1 {
	if ifForumPostActivation_i > -1 && ifForumPostActivation_ii > -1 {
		return 1
	}

	// ФОРУМ, проверка захода на регистрацию (первая страница с правилами) в режиме POST - НЕ ПРАВИЛЬНО, т.к. регистрация проходит в этом же php
	//	if urlQuery == "/forum/ucp.php?mode=register" && method == "POST" {
	//		return 1
	//	}

	// PMA
	if urlQuery == "/pma/scripts/setup.php" {
		return 1
	}

	// ifForumInfo := strings.Index(urlQuery, "/forum/app.php/post/")
	if strings.Index(urlQuery, "/forum/app.php/post/") > -1 {
		return 1
	}

	if strings.Index(urlQuery, "/component/users/") > -1 {
		return 1
	}

	return 0
}

// добавить адрес в банлист в базе
func updateBanIPInBase(db *sql.DB, IP4Int int64, reason int, siteid int) {
	stmt, err := db.Prepare("INSERT INTO blacklistip SET siteid = ?, ip = ?, reason = ? ON DUPLICATE KEY UPDATE ban_count = ban_count + 1, lastupdate = NOW()")
	checkErr(err)

	res, err := stmt.Exec(siteid, IP4Int, reason)
	checkErr(err)
	_ = res
}

// обрезка строки до 250 символов
func truncateString(str string, num int) string {
	bnoden := str
	if len(str) > num {
		if num > 3 {
			num -= 3
		}
		bnoden = str[0:num] + "..."
	}
	return bnoden
}

// заполнение струтуры данными о посетителе
func fillReceiveIP(siteid int, c *gin.Context) receiveIpStruct {
	var receiveIP receiveIpStruct
	receiveIP.siteid = siteid
	receiveIP.ip = IP4toInt(truncateString(c.PostForm("ip"), 15))
	receiveIP.date = str2int(truncateString(c.PostForm("date"), 11))
	receiveIP.url = truncateString(c.PostForm("url"), 250)
	receiveIP.ownervisit = str2int(truncateString(c.PostForm("ownervisit"), 1))
	receiveIP.agent = truncateString(c.PostForm("agent"), 250)
	receiveIP.refer = truncateString(c.PostForm("refer"), 250)
	receiveIP.method = truncateString(c.PostForm("method"), 4)
	return receiveIP
}
